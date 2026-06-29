# TASK-048: AgentChatController（对话 API + SSE）

**目标：** 实现 spec §6.2 的 6 个对话端点：发起对话(SSE)/会话内发消息/对话列表/详情/消息列表/删除；SSE 响应头与分块输出对接 `AgentRuntime.runStream`。
**范围：**
- 只允许新建:
  - `app/Http/Controllers/Api/AgentChatController.php`
  - `app/Http/Requests/Agent/StartChatRequest.php`、`SendMessageRequest.php`
  - `app/Http/Resources/ConversationResource.php`、`MessageResource.php`
- 禁止: 改路由；改运行时实现
**依赖：** 需要 TASK-043、TASK-044、TASK-045、TASK-046 先完成
**预估时间：** 4 小时
