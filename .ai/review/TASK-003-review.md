Now I have all the code. Let me write the review.

## Architecture

测试架构整体设计合理。15 个测试文件严格按服务划分，每个文件聚焦单一服务，边界清晰。`TestCase` 作为共享基础设施，统一管理 SQLite `:memory:` 数据库 schema 和中间件注册，所有测试继承它获得一致的运行环境。setUp/tearDown 中的租户上下文创建和清理模式统一。Mock 外部 HTTP 服务（Stripe/PayPal/UnionPay）使用 Laravel `Http::fake()`，Mockery 仅在 PayPal 部分模拟（绕过 `getAccessToken` 依赖），方案可接受。

**问题**：TestCase 中 schema 定义膨胀至 ~560 行，后续维护成本高。建议抽取 `setUpSchema()` 分组或使用 trait 按模块拆分。

## Code Quality

命名规范统一：类名 `XxxServiceTest`，方法名 `test_xxx_yyy_zzz`，常量用 `private const`。每个测试文件有清晰的中文 docblock 说明覆盖范围，`// ----------` 分隔线按功能分组，可读性好。辅助方法（`preInsertPaymentPassword`、`createPaymentOrder`、`createTestPlugin`、`assignPermissionsToRole`）封装得当，避免重复代码。测试遵循 Arrange-Act-Assert 模式。

**问题**：`RbacServiceTest` 中 `test_get_tenant_roles_returns_system_and_tenant_roles` 没有调用 `RbacService::getTenantRoles()`，而是直接用 `DB::table('roles')` 手写查询——测试验证的是手写 SQL 而非服务方法，偏离了单元测试目标。

## Type Safety

测试中对返回值的类型断言充分：`assertIsArray`、`assertIsBool`、`assertIsInt`、`assertGreaterThan(0, ...)` 等使用正确。`json_decode` 后的数组字段都有断言验证。

**问题**：`test_trigger_records_alert_to_database` 第 39 行 `$this->assertEquals('1001', $alert->tenant_id)` 将 tenant_id 与字符串比较。SQLite 驱动可能返回整数，如果源码中 tenant_id 是 `int` 类型，此断言在严格模式下可能产生 false positive。建议使用 `assertEquals(1001, ...)` 或 `assertSame` 明确意图。类似问题出现在 `AlertServiceTest`、`ExportServiceTest`、`PerformanceServiceTest` 等多处。

## Security

安全测试覆盖良好：
- **支付密码**：验证正确/错误密码、功能禁用、记录不存在等分支
- **支付限额**：单笔限额、日限额的通过和拒绝场景
- **风控**：高频失败拦截、冷却期内持续拦截
- **Webhook 签名**：Stripe HMAC-SHA256 验签、PayPal API 验签、无效签名拒绝、未配置 secret 异常
- **RBAC**：系统角色不可修改/删除、权限验证、角色名回退逻辑
- **租户隔离**：AlertService、RateLimitService、PluginService、ExportService、CacheService、PerformanceService 均有跨租户隔离断言
- **文件下载**：跨租户下载拒绝

**问题**：缺少对 **XSS 向量注入** 的测试（如告警消息包含 `<script>` 标签时是否正确处理）。缺少对 **支付密码暴力破解** 的测试（连续错误密码后是否锁定）。

## Performance

- 测试使用 SQLite `:memory:`，执行速度快（单测试 < 0.2s）
- `PaymentSecurityServiceTest::test_set_and_verify_payment_password_with_correct_password` 耗时 0.167s，主要因 `Hash::make()` 计算密集，可接受
- 无 N+1 查询问题——测试中使用 `DB::table()` 直接查询
- `PerformanceServiceTest` 测试的是内存中的聚合逻辑（`getAggregated`），不涉及实际 I/O

**问题**：`PluginServiceTest::setUp` 中 `mkdir($this->pluginsDir, 0755, true)` 在 `base_path('plugins')` 创建实际目录。如果测试异常中断且 tearDown 未执行，会在项目根目录留下垃圾文件。建议使用 `sys_get_temp_dir()` 作为临时目录。

## Potential Bugs

**P1 — PHPUnit 缓存文件被提交**：`.phpunit.cache/test-results` 是自动生成的构建产物，不应纳入版本控制。该文件包含环境特定数据，会导致无意义的 diff 和合并冲突。应加入 `.gitignore`。

**P2 — 测试缺陷计数下降不稳定**：`.phpunit.cache/test-results` 中多个 ControllerTest 的 defects 从 8 降至 7（如 `test_tenant_admin_can_access_own_tenant_members`、`test_end_user_can_access_tenant_data`）。缺陷计数应在同一代码版本上稳定——下降可能表明测试间存在状态泄漏或 setUp 不完整。

**P3 — QueueServiceTest 环境依赖**：多个测试用 `if (! $service->isHorizonAvailable())` 分支，使测试行为依赖运行环境。在 CI 无 Horizon 时走一个分支，有 Horizon 时走另一个——这不是确定性测试。应通过 Mock 使行为一致。

**P4 — RbacServiceTest 绕过服务层**：`test_get_tenant_roles_returns_system_and_tenant_roles` 通过 `DB::table('roles')` 直接查询验证，注释说明是因为 `Role::permissions()` belongsToMany 外键推导问题（`role_role_id` vs `role_id`）。这暴露了 `Role` 模型关系定义的 bug，但测试选择绕过而非修复。建议在 `Role` 模型中显式指定 pivot 列名。

**P5 — ExportServiceTest 缺少 user_id 列**：`test_cleanup_old_tasks_deletes_expired_tasks` 中 `cleanupOldTasks` 可能依赖 `completed_at` 或 `created_at` 字段的索引查询。当前 TestCase 中 `export_tasks` 表的 `created_at` 来自 `timestamps()`，但 cleanup 逻辑可能需要 `completed_at` 非空条件——测试通过可能只是巧合。

**P6 — PerformanceServiceTest P95 断言过于宽松**：`test_p95_calculation_with_multiple_samples` 第 301 行 `$this->assertGreaterThanOrEqual(90.0, $aggregated['p95'])` 对 10 个样本 [10,20,...,100] 的 P95 期望值应为 100（或至少 95），但断言只要求 >= 90。如果 P95 计算逻辑出错返回 90，测试仍然通过。

## Verdict

**PASS**

**【建议改进】**（非阻塞）：

1. 将 `.phpunit.cache/test-results` 加入 `.gitignore`，移除已跟踪的缓存文件
2. `TestCase::setUpDatabase()` schema 过长（~560 行），建议按模块拆分为 trait
3. `RbacServiceTest::test_get_tenant_roles_returns_system_and_tenant_roles` 应调用 `RbacService::getTenantRoles()` 而非手写 SQL
4. `QueueServiceTest` 中环境条件分支应通过 Mock Horizon 替代，确保确定性行为
5. `PluginServiceTest` 临时目录应使用 `sys_get_temp_dir()` 而非 `base_path('plugins')`
6. tenant_id 断言统一使用整数比较：`assertEquals(1001, ...)` 而非 `assertEquals('1001', ...)`
7. `PerformanceServiceTest::test_p95_calculation_with_multiple_samples` 的 P95 断言应收紧为 `assertGreaterThanOrEqual(95.0, ...)`
8. `Role` 模型 `belongsToMany` 关系应显式指定 pivot 列名（`role_id`），消除 `RbacServiceTest` 中的 workaround
