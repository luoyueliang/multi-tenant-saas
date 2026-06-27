# TASK-002: 修复测试基础设施 & 解除 TASK-001 阻塞

**Sprint:** sprint-001  
**状态:** READY  
**预估时间:** 4 小时  
**依赖:** 无

---

## 目标

修复 `TestCase::setUpDatabase()` 缺失表定义导致的 32 个测试 ERROR，使全部 76 个测试通过；然后解除并完成 TASK-001a/b/c 三个阻塞子任务（安全漏洞修复 + 数据隔离修复 + i18n 补齐）。

---

## 背景

TASK-001 新增了 9 个 migration 文件（2026-06-27），但测试基类 `tests/TestCase.php` 的 `setUpDatabase()` 从未同步更新。测试使用 SQLite `:memory:`，不走 migration，导致新增 Service 的测试全部报 "table not found"。

当前测试结果：76 tests, **32 ERRORS**, 44 passed。

---

## 范围

**只允许修改：**
- `tests/TestCase.php` — 补齐缺失的表定义
- `src/Services/StripeService.php` — webhook 签名验证 (TASK-001a)
- `src/Services/PayPalService.php` — webhook 签名验证 (TASK-001a)
- `src/Services/UnionPayService.php` — RSA 签名验证 (TASK-001a)
- `src/Services/SocialiteService.php` — state 校验 (TASK-001b)
- `src/Services/AlertService.php` — orWhereNull 修复 (TASK-001b)
- `src/Services/RateLimitService.php` — orWhereNull 修复 (TASK-001b)
- `src/Services/PluginService.php` — orWhereNull 修复 (TASK-001b)
- `src/Services/QueueService.php` — Horizon 硬依赖 (TASK-001b)
- `src/Services/ExportService.php` — 下载权限检查 (TASK-001b)
- `src/Services/PerformanceService.php` — 时间窗口公式 (TASK-001c)
- `lang/en/common.php`, `lang/en/payment.php` — 翻译键补全 (TASK-001c)
- `lang/zh_CN/common.php`, `lang/zh_CN/payment.php` — 翻译键补全 (TASK-001c)

**禁止：**
- 修改 app/ 目录（控制器、路由、中间件）
- 修改数据库 migration 文件
- 新增依赖包
- 修改 Model 层

---

## 验收标准

### 第一部分：测试基础设施修复

- [ ] `tests/TestCase.php` — `setUpDatabase()` 补齐以下缺失表：
  - `user_preferences` — 用户偏好设置
  - `structured_logs` — 结构化日志
  - `alert_rules` — 告警规则
  - `alert_history` — 告警历史
  - `export_tasks` — 导出任务
  - `api_versions` — API 版本
  - `plugins` — 插件
  - `plugin_dependencies` — 插件依赖
  - `rate_limit_rules` — 限流规则
  - `payment_security_passwords` — 支付安全密码
  - `payment_security_limits` — 支付限额
  - `payment_security_logs` — 支付安全日志
  - `oauth_accounts` — OAuth 账号绑定
  - `sessions` — 会话（如需要）
  - `cache` — 缓存表（如需要）
- [ ] 运行 `vendor/bin/phpunit` — 全部 76+ 测试通过，0 ERROR

### 第二部分：TASK-001a — 支付 Webhook 安全修复

- [ ] `StripeService.php` — `withBasicAuth()` 改为 `withToken()`（Bearer Token）
- [ ] `StripeService.php` — Webhook secret 未配置时抛出异常，不再跳过校验
- [ ] `PayPalService.php` — 新增 `verifyWebhookSignature()` 方法
- [ ] `UnionPayService.php` — 实现真实 RSA 签名验证，不再返回 true

### 第三部分：TASK-001b — 租户隔离与安全修复

- [ ] `SocialiteService.php` — `$state` 为 null 时拒绝请求
- [ ] `AlertService` / `RateLimitService` / `PluginService` — `orWhereNull('tenant_id')` 移入 `where()` 闭包
- [ ] `QueueService.php` — 移除顶部 `use Horizon\...`，改用 `class_exists()` 条件判断
- [ ] `ExportService.php` — 下载前增加用户级权限检查

### 第四部分：TASK-001c — 翻译键 & 时间窗口修复

- [ ] `lang/en/payment.php` — 补充缺失的 `payment.*` 翻译键
- [ ] `lang/zh_CN/payment.php` — 补充缺失的 `payment.*` 翻译键
- [ ] `lang/en/common.php` — 补充缺失的 `common.*` 翻译键
- [ ] `lang/zh_CN/common.php` — 补充缺失的 `common.*` 翻译键
- [ ] `PerformanceService.php` — 时间窗口公式修正为 `floor(time() / ($windowMinutes * 60)) * ($windowMinutes * 60)`

---

## 给 AI 的补充说明

### 表结构参考

表定义必须与 `database/migrations/2026_06_27_*.php` 中的 schema 完全一致。测试用 SQLite `:memory:`，注意：
- SQLite 不支持 `enum`，用 `string` 替代
- SQLite 不支持 `json`，用 `text` 替代
- SQLite 不支持列级 `charset`，忽略
- 所有 `unsignedBigInteger` 在 SQLite 中为 `integer`

### 执行顺序

1. **先修复 TestCase** — 这是所有后续工作的基础
2. **验证测试全通过** — `vendor/bin/phpunit`
3. **并行修复 TASK-001a/b/c** — 三个子任务互相独立
4. **再次运行测试** — 确保修复没有引入回归

### orWhereNull 闭包写法

```php
// 错误：->orWhereNull('tenant_id')
// 正确：
->where(function ($q) {
    $q->where('tenant_id', $this->tenantId)->orWhereNull('tenant_id');
})
```

---

## 任务分解

### T2.1 TestCase 表补齐（1小时）
- 对照 9 个 migration 文件，在 `setUpDatabase()` 中补齐 15 张缺失表
- 运行测试确认 0 ERROR

### T2.2 支付 Webhook 安全修复（1小时）
- 修复 StripeService 认证方式 + webhook 校验
- 实现 PayPalService webhook 签名验证
- 实现 UnionPayService RSA 签名验证

### T2.3 租户隔离修复（1小时）
- 修复 SocialiteService state 校验
- 修复 AlertService/RateLimitService/PluginService orWhereNull
- 修复 QueueService Horizon 硬依赖
- 修复 ExportService 下载权限

### T2.4 翻译 & 时间窗口修复（0.5小时）
- 补齐 50 个翻译键
- 修正 PerformanceService 时间窗口公式

### T2.5 最终验证（0.5小时）
- 全量测试通过
- 确认无回归

---

## 风险与依赖

### 风险
1. **表结构不一致** — TestCase 中的表定义必须与 migration 精确匹配，否则 Service 逻辑可能出错
2. **SQLite 兼容性** — 部分 MySQL 特性（enum/json）在 SQLite 中行为不同
3. **PayPal/UnionPay 验签** — 无法在测试环境真实验证，需 mock

### 依赖
1. 无外部依赖
2. 依赖现有 migration 文件作为表结构参考

---

## 状态流转记录

| 时间 | 状态 | 备注 |
|------|------|------|
| 2026-06-27 | READY | 创建任务 |
