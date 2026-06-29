# Sprint: Agent Framework 智能体框架

## 目标
为多租户 SaaS 框架交付可配置、可复用的 AI 智能体（数字员工）基础设施：Agent CRUD、工具注册与 Function Calling、ReAct 运行时（含 SSE 流式）、多轮对话记忆与压缩、用量监控与降级容错，满足多租户隔离与 IdGenerator 主键规范。

## Task 列表

### TASK-033: AI 推理服务契约与驱动抽象（非流式）
**目标：** 新增 `AiTextService`（chat/complete，支持 tools 参数与 tool_calls 解析），作为 AgentRuntime 推理引擎；含可插拔 Driver 抽象 + Mock 驱动 + OpenAI 兼容驱动 + DTO。
**范围：**
- 只允许修改/新建:
  - `src/Contracts/AiTextServiceContract.php`（新建）
  - `src/Services/Ai/AiTextService.php`（新建）
  - `src/Services/Ai/Drivers/AiDriverContract.php`（新建）
  - `src/Services/Ai/Drivers/OpenAiCompatibleDriver.php`（新建，用已装 HTTP 客户端，读 `config/ai.php` providers）
  - `src/Services/Ai/Drivers/MockAiDriver.php`（新建，供测试，可按预设脚本返回含 tool_calls 的响应）
  - `src/Services/Ai/AiResponse.php`（DTO，新建）
  - `src/TenancyServiceProvider.php`（仅 register 追加 `AiTextServiceContract` 单例绑定）
- 禁止: 实现流式接口（归 TASK-034）；新建 `config/ai.php`（**已存在，仅读取**）；改 Agent 相关任何文件；改数据库；新增第三方包
**依赖：** 无
**预估时间：** 4 小时

### TASK-034: AI 推理服务流式接口（streamChat + SSE）
**目标：** 在 `AiTextService` 增加 `streamChat(): Generator`，逐 token 产出文本并在流中识别 tool_calls，供 AgentRuntime 流式模式消费。
**范围：**
- 只允许修改:
  - `src/Contracts/AiTextServiceContract.php`（追加 streamChat 签名）
  - `src/Services/Ai/AiTextService.php`
  - `src/Services/Ai/Drivers/AiDriverContract.php`
  - `src/Services/Ai/Drivers/OpenAiCompatibleDriver.php`
  - `src/Services/Ai/Drivers/MockAiDriver.php`
  - `src/Services/Ai/StreamChunk.php`（DTO，新建）
- 禁止: 改 AgentRuntime；改非流式 chat 逻辑；改数据库
**依赖：** 需要 TASK-033 先完成
**预估时间：** 3 小时

### TASK-035: Agent 数据库迁移（5 张表）
**目标：** 按 spec §3 创建 `agents`/`agent_tools`/`agent_conversations`/`agent_conversation_messages`/`agent_tool_logs` 5 张表迁移；主键均用 `IdGenerator` 生成 BIGINT（禁止 auto_increment），索引按 spec 定义。
**范围：**
- 只允许新建:
  - `database/migrations/2026_06_29_000001_create_agents_table.php`
  - `database/migrations/2026_06_29_000002_create_agent_tools_table.php`
  - `database/migrations/2026_06_29_000003_create_agent_conversations_table.php`
  - `database/migrations/2026_06_29_000004_create_agent_conversation_messages_table.php`
  - `database/migrations/2026_06_29_000005_create_agent_tool_logs_table.php`
- 禁止: 改其它已有迁移；建 Eloquent Model（归 TASK-037）；改 seed
**依赖：** 无
**预估时间：** 3 小时

### TASK-036: Agent 模块事件类
**目标：** 新建 spec §7.3 的 8 个事件类（AgentCreated/Enabled/Disabled、ConversationStarted/Ended、ToolCalled/ToolCallCompleted/ToolCallFailed），均含 tenant_id/agent_id 等只读属性，跟随现有 `src/Events/TenantCreated.php` 写法。
**范围：**
- 只允许新建:
  - `src/Events/AgentCreated.php`、`src/Events/AgentEnabled.php`、`src/Events/AgentDisabled.php`
  - `src/Events/ConversationStarted.php`、`src/Events/ConversationEnded.php`
  - `src/Events/ToolCalled.php`、`src/Events/ToolCallCompleted.php`、`src/Events/ToolCallFailed.php`
- 禁止: 改已有事件；注册监听器；改业务服务
**依赖：** 无
**预估时间：** 1.5 小时

### TASK-037: Eloquent 模型（5 个）
**目标：** 为 5 张表建模型，复用现有 `BelongsToTenant`+`HasGlobalId` Concern；定义 JSON cast（tools/kb_ids/feature_keys/model_config/token_usage/tool_calls/metadata）、关联（Agent→Conversations→Messages、Conversation→ToolLogs）；`$primaryKey` 设为对应 `*_id`。
**范围：**
- 只允许新建:
  - `src/Models/Agent.php`、`src/Models/AgentTool.php`、`src/Models/AgentConversation.php`、`src/Models/AgentConversationMessage.php`、`src/Models/AgentToolLog.php`
- 禁止: 改迁移；改其它模型；改 Concerns
**依赖：** 需要 TASK-035 先完成
**预估时间：** 2.5 小时

### TASK-038: 服务契约与 DTO
**目标：** 新建 spec §4 的 4 个服务契约接口（AgentServiceContract/AgentRuntimeContract/ToolRegistryContract/AgentMonitorContract）及 DTO（`AgentResponse`、`Tool`）。
**范围：**
- 只允许新建:
  - `src/Contracts/AgentServiceContract.php`、`src/Contracts/AgentRuntimeContract.php`、`src/Contracts/ToolRegistryContract.php`、`src/Contracts/AgentMonitorContract.php`
  - `src/Services/Agent/Dto/AgentResponse.php`、`src/Services/Agent/Dto/Tool.php`
- 禁止: 写实现类；改已有契约
**依赖：** 无（可与 TASK-033/035/036 并行）
**预估时间：** 2 小时

### TASK-039: ToolRegistry 实现
**目标：** 实现 `ToolRegistry`：register/all/get/getToolDefinitions（转 Function Calling 格式）/execute（解析 handler_class，容器实例化并调用 `__invoke`）/isAvailable；从 `agent_tools` 表与运行时注册双源合并；execute 显式传 tenantId。
**范围：**
- 只允许新建:
  - `src/Services/Agent/ToolRegistry.php`
  - `src/Services/Agent/Contracts/ToolHandlerContract.php`（工具处理类统一接口）
- 禁止: 写具体业务工具 handler；改控制器；改路由
**依赖：** 需要 TASK-037、TASK-038 先完成
**预估时间：** 3.5 小时

### TASK-040: AgentService 实现（CRUD + 配置管理）
**目标：** 实现 `AgentService`：create/update/delete/find/listForTenant、enable/disable、updateModelConfig/getEffectiveModelConfig（合并 `config/ai.php` 默认）、attachTools/detachTools/getAgentTools、attachKnowledgeBases/detachKnowledgeBases；分发对应事件；强制 tenant_id 来自 `TenantContextContract`。
**范围：**
- 只允许修改/新建:
  - `src/Services/Agent/AgentService.php`（新建）
  - `src/TenancyServiceProvider.php`（仅追加 `AgentServiceContract` 绑定）
- 禁止: 实现模板克隆（归 TASK-041）；改模型；改控制器
**依赖：** 需要 TASK-036、TASK-037、TASK-038 先完成
**预估时间：** 4 小时

### TASK-041: 预置 Agent 模板与克隆
**目标：** 实现 `getBuiltinTemplates()`（框架提供 8 个角色骨架空模板：客服/销售/营销/数据分析等，feature_keys 留空由业务层填充）与 `cloneFromTemplate()`（复制 system_prompt/tools/kb_ids/feature_keys/model_config 到目标租户）。
**范围：**
- 只允许修改/新建:
  - `src/Services/Agent/AgentService.php`（追加两方法）
  - `src/Services/Agent/BuiltinAgentTemplates.php`（新建，模板定义数据）
  - `database/seeders/AgentBuiltinTemplatesSeeder.php`（新建，可选）
- 禁止: 改业务功能点定义；改前端
**依赖：** 需要 TASK-040 先完成
**预估时间：** 2.5 小时

### TASK-042: AgentMonitor 实现
**目标：** 实现 `AgentMonitor`：logConversationTurn/logToolCall（写 `agent_tool_logs` 与 conversation token_usage）、getTokenUsage/getPerformanceMetrics/getCostEstimate（按 provider 模型价格表估算）；只读 tenant 隔离。
**范围：**
- 只允许修改/新建:
  - `src/Services/Agent/AgentMonitor.php`（新建）
  - `src/Services/Agent/AiPricing.php`（新建，模型→单价映射，可由 `config/ai.php` 扩展读取）
  - `src/TenancyServiceProvider.php`（仅追加 `AgentMonitorContract` 绑定）
- 禁止: 改运行时；改控制器
**依赖：** 需要 TASK-037、TASK-038 先完成
**预估时间：** 3 小时

### TASK-043: AgentRuntime — ReAct 循环（非流式）
**目标：** 实现 `AgentRuntime.run()`：加载 Agent 配置 → 构建上下文（system_prompt+历史+新消息）→ 调 `AiTextService.chat(messages, tools, model_config)` → 文本则返回 / tool_calls 则经 `ToolRegistry.execute` 后将结果以 role=tool 追加回上下文 → 受 max_tool_calls 限制循环 → 经 `AgentMonitor` 记日志；实现 getConversationContext/continueWithToolResults。
**范围：**
- 只允许修改/新建:
  - `src/Services/Agent/AgentRuntime.php`（新建）
  - `src/Services/Agent/Dto/AgentResponse.php`（补全字段）
  - `src/TenancyServiceProvider.php`（仅追加 `AgentRuntimeContract` 绑定）
- 禁止: 实现流式（归 TASK-044）；实现记忆压缩（归 TASK-045）；实现降级（归 TASK-046）
**依赖：** 需要 TASK-033、TASK-037、TASK-038、TASK-039、TASK-040、TASK-042 先完成
**预估时间：** 4 小时

### TASK-044: AgentRuntime — SSE 流式执行
**目标：** 实现 `runStream(): Generator`：基于 `AiTextService.streamChat()` 逐 chunk 产出；遇 tool_calls 暂停流式 → 执行工具 → 结果入上下文 → 继续流式；末尾发送 `[DONE]`；中途记日志。
**范围：**
- 只允许修改:
  - `src/Services/Agent/AgentRuntime.php`（追加 runStream）
  - `src/Contracts/AgentRuntimeContract.php`（如需补流式 DTO）
- 禁止: 改 AiTextService；改控制器
**依赖：** 需要 TASK-034、TASK-043 先完成
**预估时间：** 3.5 小时

### TASK-045: MemoryCompressor 记忆压缩
**目标：** 实现 `compressMemory()`：对会话超过阈值（单次对话 max_tokens 默认 8000）的旧消息分批用 `AiTextService` 生成摘要，替换为单条 role=system 摘要消息；在 `AgentRuntime.run` 入口自动触发；提供 getConversationContext 的截断策略。
**范围：**
- 只允许修改/新建:
  - `src/Services/Agent/MemoryCompressor.php`（新建）
  - `src/Services/Agent/AgentRuntime.php`（接入压缩触发与上下文截断）
  - `src/Contracts/AgentRuntimeContract.php`（确认 compressMemory 签名）
- 禁止: 改迁移；改 AiTextService
**依赖：** 需要 TASK-033、TASK-037、TASK-043 先完成
**预估时间：** 3 小时

### TASK-046: 错误处理与降级容错
**目标：** 在 AgentRuntime 注入容错：AI 驱动抛异常 → 自动切换 model_config.fallback_provider 重试；工具执行失败 → 错误信息以 role=tool 返给 AI 由其决策；超时 → 返回已生成内容 + 超时提示；循环超 max_tool_calls → 强制总结返回。
**范围：**
- 只允许修改:
  - `src/Services/Agent/AgentRuntime.php`（包装 try/catch + 降级分支）
  - `src/Services/Agent/ToolRegistry.php`（execute 异常封装为结构化错误而非抛出）
  - `src/Services/Ai/AiTextService.php`（如需 provider 切换钩子，仅追加）
  - `src/Events/ToolCallFailed.php`（如需补充字段）
- 禁止: 改接口契约签名；改控制器
**依赖：** 需要 TASK-043、TASK-044 先完成
**预估时间：** 3.5 小时

### TASK-047: AgentController（管理 API）
**目标：** 实现 spec §6.1 的 12 个管理端点：列表/详情/创建/更新/删除/enable/disable/templates/clone/model-config/tools/knowledge-bases；含 FormRequest 校验与租户隔离中间件。
**范围：**
- 只允许新建:
  - `app/Http/Controllers/Api/AgentController.php`（跟随现有控制器命名空间 `App\Http\Controllers\Api`）
  - `app/Http/Requests/Agent/CreateAgentRequest.php`、`UpdateAgentRequest.php`、`UpdateModelConfigRequest.php`、`UpdateToolsRequest.php`、`UpdateKnowledgeBasesRequest.php`
  - `app/Http/Resources/AgentResource.php`
- 禁止: 改路由（归 TASK-050）；改服务实现
**依赖：** 需要 TASK-040、TASK-041 先完成
**预估时间：** 4 小时

### TASK-048: AgentChatController（对话 API + SSE）
**目标：** 实现 spec §6.2 的 6 个对话端点：发起对话(SSE)/会话内发消息/对话列表/详情/消息列表/删除；SSE 响应头与分块输出对接 `AgentRuntime.runStream`。
**范围：**
- 只允许新建:
  - `app/Http/Controllers/Api/AgentChatController.php`
  - `app/Http/Requests/Agent/StartChatRequest.php`、`SendMessageRequest.php`
  - `app/Http/Resources/ConversationResource.php`、`MessageResource.php`
- 禁止: 改路由；改运行时实现
**依赖：** 需要 TASK-043、TASK-044、TASK-045、TASK-046 先完成
**预估时间：** 4 小时

### TASK-049: AgentStatsController 与 ToolController
**目标：** 实现 spec §6.3 的 4 个监控端点（stats/token-usage/cost/tool-logs）与 §6.4 的 5 个工具管理端点（列表/详情/注册/更新/删除）。
**范围：**
- 只允许新建:
  - `app/Http/Controllers/Api/AgentStatsController.php`、`app/Http/Controllers/Api/ToolController.php`
  - `app/Http/Requests/Agent/RegisterToolRequest.php`、`UpdateToolRequest.php`
  - `app/Http/Resources/ToolResource.php`、`ToolLogResource.php`
- 禁止: 改路由；改 ToolRegistry 实现
**依赖：** 需要 TASK-039、TASK-042 先完成
**预估时间：** 3 小时

### TASK-050: 路由注册与服务容器绑定
**目标：** 在 `routes/api.php` 的 `/api/v1` 组下注册全部 Agent/Chat/Stats/Tool 路由（含 SSE 路由，import 用 `App\Http\Controllers\Api\*`）；在 `TenancyServiceProvider::register` 校验/补齐 4 个 Agent 服务契约单例绑定。
**范围：**
- 只允许修改:
  - `routes/api.php`（仅追加 Agent 路由组）
  - `src/TenancyServiceProvider.php`（仅追加/校验 4 个绑定，确保未遗漏）
- 禁止: 改控制器；改现有路由
**依赖：** 需要 TASK-047、TASK-048、TASK-049 先完成
**预估时间：** 1.5 小时

### TASK-051: 单元测试 — AgentService + ToolRegistry
**目标：** 覆盖 AgentService CRUD/启用禁用/配置合并/工具与知识库挂载/模板克隆 happy path 与 error path；ToolRegistry 注册/发现/Function Calling 格式转换/执行/失败；使用 MockAiDriver 避免真实调用。
**范围：**
- 只允许新建:
  - `tests/AgentServiceTest.php`、`tests/ToolRegistryTest.php`、`tests/BuiltinAgentTemplatesTest.php`（跟随现有 `tests/` 扁平结构，命名空间 `MultiTenantSaas\Tests`）
- 禁止: 改生产代码；改迁移
**依赖：** 需要 TASK-039、TASK-040、TASK-041 先完成
**预估时间：** 3.5 小时

### TASK-052: 单元/集成测试 — AgentRuntime
**目标：** 覆盖 ReAct 循环（文本回复/单轮工具/多轮工具/达上限强制总结）、流式 SSE chunk 序列、记忆压缩触发与摘要替换、provider 降级、工具失败恢复、超时返回。
**范围：**
- 只允许新建:
  - `tests/AgentRuntimeTest.php`、`tests/AgentRuntimeStreamTest.php`、`tests/MemoryCompressorTest.php`、`tests/AgentFallbackTest.php`
- 禁止: 改生产代码
**依赖：** 需要 TASK-043、TASK-044、TASK-045、TASK-046 先完成
**预估时间：** 4 小时

### TASK-053: Feature 测试 — HTTP API
**目标：** 覆盖全部 27 个端点的请求/响应/状态码/租户隔离/校验失败；SSE 端点断言事件流内容与 `[DONE]`。
**范围：**
- 只允许新建:
  - `tests/AgentControllerTest.php`、`tests/AgentChatControllerTest.php`、`tests/AgentStatsControllerTest.php`、`tests/ToolControllerTest.php`（Feature 风格，复用现有 `tests/TestCase.php`）
- 禁止: 改生产代码；改路由
**依赖：** 需要 TASK-047、TASK-048、TASK-049、TASK-050 先完成
**预估时间：** 4 小时

### TASK-054: 文档与 Swagger 注解
**目标：** 为 4 个 Controller 补 L5-Swagger 注解（项目已装 `l5-swagger`）；在 `README.md` 追加 Agent Framework 章节（概念、快速开始、API 概览、配置项）；更新 `CHANGELOG.md`。
**范围：**
- 只允许修改:
  - `app/Http/Controllers/Api/AgentController.php`、`AgentChatController.php`、`AgentStatsController.php`、`ToolController.php`（仅追加 Swagger 注解，不改逻辑）
  - `README.md`、`CHANGELOG.md`
- 禁止: 改业务逻辑；改测试
**依赖：** 需要 TASK-047、TASK-048、TASK-049 先完成
**预估时间：** 3 小时

## 注意事项
- **关键依赖更正**：spec §7.1 声称复用 `AiTextService` 等现有服务，但仓库 `src/Services/` 中并不存在（仅 `config/ai.php` 已先行配置）—— 已由 TASK-033/034 前置补齐，否则 ReAct 循环与流式无法落地。`AiImageService`/`AiVideoService` 本 Sprint 不涉及。
- **路径与仓库现状对齐**（已核实）：控制器实际位于 `app/Http/Controllers/Api/`（命名空间 `App\Http\Controllers\Api`），非 spec §9 的 `src/Http/Controllers/`；Resource 在 `app/Http/Resources/`；测试为 `tests/` 扁平结构（`MultiTenantSaas\Tests`）；Concerns `BelongsToTenant`/`HasGlobalId` 已存在可直接 use；迁移命名跟随 `2026_06_29_00000x_` 惯例。
- **每个 Task 独立可执行，不超过 4 小时**；TASK-033/040/043/047/048/052 为 4h 临界任务，实现时若超限可二次拆分。
- **依赖链（关键路径）**：033→034→044 ; 035→037→(039,042)→043→(044,045,046)→048 ; 038→039→040→041→047→050→053。无依赖 Task（033/035/036/038）可并行启动。
- **多租户隔离**：所有 Service 经 `TenantContextContract` 取 tenant_id；Model 用 `BelongsToTenant`；`ToolRegistry.execute` 显式传 tenantId；Feature 测试必须含跨租户越权用例。
- **ID 规范**：迁移主键统一用 `IdGenerator` 生成 BIGINT，Model 用 `HasGlobalId`，禁止 auto_increment。
- **验收对照**：spec §10 共 15 条验收标准，由 039/040/041/042/043/044/045/046/035 等覆盖。

---

