# TASK-038: 服务契约与 DTO

**目标：** 新建 spec §4 的 4 个服务契约接口（AgentServiceContract/AgentRuntimeContract/ToolRegistryContract/AgentMonitorContract）及 DTO（`AgentResponse`、`Tool`）。
**范围：**
- 只允许新建:
  - `src/Contracts/AgentServiceContract.php`、`src/Contracts/AgentRuntimeContract.php`、`src/Contracts/ToolRegistryContract.php`、`src/Contracts/AgentMonitorContract.php`
  - `src/Services/Agent/Dto/AgentResponse.php`、`src/Services/Agent/Dto/Tool.php`
- 禁止: 写实现类；改已有契约
**依赖：** 无（可与 TASK-033/035/036 并行）
**预估时间：** 2 小时
