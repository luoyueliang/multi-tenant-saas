# TASK-039: ToolRegistry 实现

**目标：** 实现 `ToolRegistry`：register/all/get/getToolDefinitions（转 Function Calling 格式）/execute（解析 handler_class，容器实例化并调用 `__invoke`）/isAvailable；从 `agent_tools` 表与运行时注册双源合并；execute 显式传 tenantId。
**范围：**
- 只允许新建:
  - `src/Services/Agent/ToolRegistry.php`
  - `src/Services/Agent/Contracts/ToolHandlerContract.php`（工具处理类统一接口）
- 禁止: 写具体业务工具 handler；改控制器；改路由
**依赖：** 需要 TASK-037、TASK-038 先完成
**预估时间：** 3.5 小时
