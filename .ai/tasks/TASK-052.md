# TASK-052: 单元/集成测试 — AgentRuntime

**目标：** 覆盖 ReAct 循环（文本回复/单轮工具/多轮工具/达上限强制总结）、流式 SSE chunk 序列、记忆压缩触发与摘要替换、provider 降级、工具失败恢复、超时返回。
**范围：**
- 只允许新建:
  - `tests/AgentRuntimeTest.php`、`tests/AgentRuntimeStreamTest.php`、`tests/MemoryCompressorTest.php`、`tests/AgentFallbackTest.php`
- 禁止: 改生产代码
**依赖：** 需要 TASK-043、TASK-044、TASK-045、TASK-046 先完成
**预估时间：** 4 小时
