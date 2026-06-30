## Architecture

`getBuiltinTemplates()` 和 `cloneFromTemplate()` 的职责划分正确——纯数据定义（`BuiltinAgentTemplates`）与业务逻辑（`AgentService`）分离。`BuiltinAgentTemplates` 作为 `final class` 纯静态数据类，无外部依赖，模块边界清晰。`cloneFromTemplate()` 的设计意图合理：admin 发起克隆，传入目标 `$tenantId`，不依赖当前请求上下文。

## Code Quality

- 两方法实现简洁，`getBuiltinTemplates()` 单行委托，`cloneFromTemplate()` 逻辑清晰。
- 覆盖白名单（`CLONE_OVERRIDABLE_KEYS`）定义在数据类中，Service 层通过 `array_intersect_key` + `array_flip` 消费，解耦得当。
- `metadata` 中记录 `cloned_from_template` 溯源信息，便于后续追踪。
- 命名规范、注释风格与现有代码一致。

## Type Safety

- `getBuiltinTemplates()` 返回 `SupportCollection`，与 `AgentServiceContract` 接口一致。
- `cloneFromTemplate()` 的 `$allowedOverrides` 通过 `array_intersect_key` 过滤后，类型仍为 `array<string, mixed>`，与 `Agent::create()` 的参数类型兼容。
- `BuiltinAgentTemplates::find()` 接受 `int`，`cloneFromTemplate()` 传入 `int`，类型匹配。
- `Agent::$fillable` 包含所有传入字段，`casts()` 将 `tools`/`kb_ids`/`feature_keys`/`model_config`/`metadata` 为 `array`，创建时 JSON 编码正确。

## Security

- `cloneFromTemplate()` 接受外部传入的 `$tenantId`，未通过 `resolveTenantId()` 校验——这是有意设计（admin 克隆到指定租户），但调用方需确保来源可信。上层控制器/路由层应有认证和授权保护。
- `role` 和 `system_prompt` 从模板硬编码复制，不允许通过 `$overrides` 覆盖（不在 `CLONE_OVERRIDABLE_KEYS` 中），防止篡改核心角色定义。
- `is_builtin` 硬编码为 `true`，不可被覆盖。
- 无 SQL 注入风险，全部通过 Eloquent 参数化查询。

## Performance

- `BuiltinAgentTemplates::all()` 内部有静态缓存 `self::$cache`，避免重复构建模板数组。
- `cloneFromTemplate()` 单次 `Agent::create()` + 一次 `fresh()` SELECT，无 N+1 问题。
- 事务范围最小化（仅 create + commit），无长时间持锁风险。

## Potential Bugs

1. **`BuiltinAgentTemplates::$cache` 静态缓存在 Octane/Swoole 下跨请求共享**：如果运行时通过 `config()` 修改了 `ai.default_provider`，`defaultModelConfig()` 的返回值不会更新（缓存的 `$modelConfig` 引用的是旧值）。`clearCache()` 存在但需要手动调用。这是一个已知的长进程风险。
2. **`cloneFromTemplate()` 中 `$tenantId` 未经校验**：如果传入的 `$tenantId` 不存在，`Agent::create()` 会因外键约束（如果存在）或数据一致性问题失败。当前无显式校验，依赖数据库约束兜底。
3. **`$allowedOverrides` 的 `??` 回退逻辑**：如果调用方显式传入 `overrides['enabled'] = false`，结果正确。但如果传入 `overrides['name'] = null`，`$allowedOverrides['name'] ?? $template['name']` 会回退到模板名称——这是安全的，但可能不符合调用方"清空"的预期。

## Verdict

**PASS**

【建议改进】（非阻塞）：

1. `BuiltinAgentTemplates` 的静态缓存在 Octane 长进程下可能返回过期配置——可改为在 `definitions()` 中每次重新调用 `self::defaultModelConfig()` 并比较，或使用请求级缓存而非类静态属性。
2. `cloneFromTemplate()` 的 `$tenantId` 参数可考虑增加一个 `Tenant::find($tenantId)` 的存在性校验，提前抛出有意义的错误信息，而非依赖数据库层报错。