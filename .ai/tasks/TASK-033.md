# TASK-033: AI 推理服务契约与驱动抽象（非流式）

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
