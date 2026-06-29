Let me examine the actual service files mentioned in the task scope to fully assess the changes.
Let me check for the duplicate import and the actual service files in scope.
Now let me read all the core files in scope.
Let me also check the AiResponse class and get the actual git diff for the core files.
Let me check whether the AI service files are newly added or modified in this commit.
Let me check when the AI service files were actually committed.
Now I have all the context needed. Here's my review:

---

## Architecture

整体分层清晰：Contract → Service → Driver 三层解耦，`yield from` 委托模式正确。`StreamChunk` 作为 DTO 使用 `readonly` promoted properties，适合跨边界传输。`AiDriverContract` 同时定义 `chat()` 和 `streamChat()` 接口合理。

**问题：**
- `StreamChunk::fromOpenAiDelta()` 是**死代码**——`OpenAiCompatibleDriver` 内联了完整 SSE 解析逻辑，从未调用此工厂方法。两者解析逻辑重复且行为不一致（见下文）。
- `tests/TestCase.php` 新增了 `coupons` / `coupon_usages` 表结构，与 TASK-034 **完全无关**，属于越界变更，且插入位置破坏了文件的逻辑分组（插在 OAuth 表和 `usage_records` 之间）。

## Code Quality

命名 PascalCase，语义清晰，文件结构一致。`buildPayload()` 抽象合理，避免 `chat()` 和 `streamChat()` 重复构建请求体。

- `StreamChunk::fromOpenAiDelta()` 与 `OpenAiCompatibleDriver` 第 129-182 行存在**功能重复**——两处都解析 OpenAI SSE delta 格式，但 driver 内联版多了 tool_calls 缓冲逻辑。应统一为一处。
- `MockAiDriver::streamChat()` 不支持 `scriptedResponses`，只硬编码逐字符产出——与 `chat()` 的脚本化能力不对称，测试覆盖面不足。

## Type Safety

PHP 8.1+ `readonly` promoted properties 使用正确。`Generator<int, StreamChunk>` 返回类型注解完整。

- `StreamChunk::$toolCalls` 类型为 `array`，无形状注解。建议补充 `@var array<int, array{index: int, id?: string, type?: string, function?: array{name?: string, arguments?: string}}>` 以提升 IDE 支持。
- `AiDriverContract::streamChat()` 的 `@return Generator<int, StreamChunk>` 注解正确。

## Security

无 OWASP 风险。API key 从 config 读取，日志仅记录 model/message_count/tool_count，未泄露敏感数据。HTTP 请求使用 Laravel Http facade，无手动拼接 URL 注入风险。超时可配置。

## Performance

Generator 模式天然内存友好，逐 chunk yield 无需缓冲完整响应流。`toolCallsBuffer` 按 index 累积，合理。无 N+1 问题。

## Potential Bugs

1. **tool_calls 双重产出**：`OpenAiCompatibleDriver::streamChat()` 在流式过程中每收到一个 `delta.tool_calls` 就 yield 一个包含增量 tool_call 的 `StreamChunk`（第 168-170 行），但在流结束时又 yield 一个包含**完整** `toolCallsBuffer` 的最终 chunk（第 175-181 行）。消费者会收到重复的 tool_call 数据——中间 chunk 有部分数据，最终 chunk 有完整数据。契约文档写的是"合并在最后一个 chunk 产出"，但实际实现是**边流边产出 + 最终合并产出**。这是语义不一致的 bug。

2. **`StreamChunk::fromOpenAiDelta()` 死代码**：从未被调用，且其解析逻辑与 driver 内联版不一致（缺少 tool_calls 缓冲）。维护两份解析逻辑是隐患。

3. **`MockAiDriver::streamChat()` 不支持 tool_calls 场景**：测试无法覆盖流式 tool_call 识别路径。

4. **`TenancyServiceProvider` 单例注册**：`$this->app->singleton(AiTextServiceContract::class, AiTextService::class)` 和 `$this->app->singleton(AiTextService::class)` 注册了两个独立单例。通过 `AiTextServiceContract` 解析和通过 `AiTextService::class` 解析会得到**不同实例**。通常只需注册契约到实现的绑定即可。

5. **`tests/TestCase.php` 越界变更**：新增 coupons / coupon_usages 表结构与 TASK-034 无关。

## Verdict

**FAIL**

【必须修复】
1. **tool_calls 双重产出**（`OpenAiCompatibleDriver.php:168-181`）：流式过程中不应 yield 增量 tool_call chunk，应只在最终 chunk 合并产出完整 tool_calls，与契约文档"合并在最后一个 chunk 产出"保持一致。或者修改契约文档说明中间也会产出增量。
2. **删除 `StreamChunk::fromOpenAiDelta()` 死代码**（`StreamChunk.php:37-75`）：此方法从未被调用，且与 driver 内联解析逻辑重复。删除它，或重构 driver 统一使用此工厂方法（二选一）。
3. **回滚 `tests/TestCase.php` 中 coupons / coupon_usages 表结构**：与 TASK-034 无关，属于越界变更。
4. **修正 `TenancyServiceProvider` 单例注册**：删除 `$this->app->singleton(AiTextService::class)` 这一行，仅保留契约绑定。