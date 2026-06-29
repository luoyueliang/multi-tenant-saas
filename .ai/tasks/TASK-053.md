# TASK-053: Feature 测试 — HTTP API

**目标：** 覆盖全部 27 个端点的请求/响应/状态码/租户隔离/校验失败；SSE 端点断言事件流内容与 `[DONE]`。
**范围：**
- 只允许新建:
  - `tests/AgentControllerTest.php`、`tests/AgentChatControllerTest.php`、`tests/AgentStatsControllerTest.php`、`tests/ToolControllerTest.php`（Feature 风格，复用现有 `tests/TestCase.php`）
- 禁止: 改生产代码；改路由
**依赖：** 需要 TASK-047、TASK-048、TASK-049、TASK-050 先完成
**预估时间：** 4 小时
