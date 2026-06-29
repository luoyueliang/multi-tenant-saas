# TASK-047: AgentController（管理 API）

**目标：** 实现 spec §6.1 的 12 个管理端点：列表/详情/创建/更新/删除/enable/disable/templates/clone/model-config/tools/knowledge-bases；含 FormRequest 校验与租户隔离中间件。
**范围：**
- 只允许新建:
  - `app/Http/Controllers/Api/AgentController.php`（跟随现有控制器命名空间 `App\Http\Controllers\Api`）
  - `app/Http/Requests/Agent/CreateAgentRequest.php`、`UpdateAgentRequest.php`、`UpdateModelConfigRequest.php`、`UpdateToolsRequest.php`、`UpdateKnowledgeBasesRequest.php`
  - `app/Http/Resources/AgentResource.php`
- 禁止: 改路由（归 TASK-050）；改服务实现
**依赖：** 需要 TASK-040、TASK-041 先完成
**预估时间：** 4 小时
