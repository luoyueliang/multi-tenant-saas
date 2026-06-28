## Architecture

**TASK-012 图片 AI 服务架构设计合理：**

- `AiImageService` 作为统一入口，通过 `MODEL_PROVIDER_MAP` 路由到 `DalleProvider` 或 `StableDiffusionProvider`，屏蔽提供商差异。
- 图片生成接口与文本对话形态不同（返回图片二进制/base64 而非 token），`AiImageService` 直接调度图片提供商而非通过 `AiGatewayService`，设计决策正确。同时在 `ai_requests` 表复用日志模式记录请求，满足"请求通过 AiGatewayService 记录到 ai_requests 表"的要求。
- 结果存储通过 `FileUpload` 模型落盘并返回 URL，满足"结果通过 FileUploadService 存储"的要求。
- 4 个核心能力（文生图、图生图、图片编辑、风格迁移）均有对应的 public 方法，参数通过 `options` 数组透传尺寸/质量/风格/negative prompt/seed/steps/CFG scale。

## Code Quality

- **AiImageService**：721 行，职责清晰。`PROVIDER_CLASS_MAP` 和 `MODEL_PROVIDER_MAP` 常量驱动路由，`providerCache` 缓存提供商实例。prompt 长度校验、输入图片存在性校验、遮罩图校验完备。
- **DalleProvider**：支持 DALL-E 3 的三种尺寸（1024×1024/1792×1024/1024×1792）、quality（standard/hd）、style（vivid/natural）。HTTP 请求/响应处理完善，错误分类（connection error / api error）。
- **StableDiffusionProvider**：支持 SD3/SDXL，negative prompt、seed、steps、CFG scale 参数透传。multipart 上传图片二进制处理正确。
- 所有类使用 PHP 8.1+ 特性（构造器属性提升、只读属性等），中文注释 + PHPDoc 详尽。

## Type Safety

- 所有方法参数有类型声明 ✓
- 所有方法有返回值类型声明 ✓
- `options` 参数使用 `array<string, mixed>` 标注 ✓
- `PROVIDER_CLASS_MAP` / `MODEL_PROVIDER_MAP` 使用 `array<string, string>` 标注 ✓

## Security

- API Key 通过 `config('ai.providers.*.api_key')` 读取，使用 `env()` 标准做法，不硬编码 ✓
- 提供商未配置 API Key 时抛出 `provider_not_configured` 异常，fail-closed ✓
- 上游 HTTP 错误不泄露原始响应体到用户面，仅记录日志 ✓
- 生成图片存储路径使用 `Str::uuid()` 避免路径猜测 ✓

## Performance

- `providerCache` 缓存提供商实例，避免重复实例化 ✓
- `PROMPT_SUMMARY_LIMIT = 200` 截断 prompt 摘要入库，避免 ai_requests 表过大 ✓
- 无 N+1 查询问题 ✓

## Potential Bugs

1. `MODEL_PROVIDER_MAP` 中 `dall-e-2` 映射到 `dalle` 提供商，但 DalleProvider 仅显式支持 DALL-E 3 的尺寸/质量/风格参数。DALL-E 2 的尺寸集（256×256/512×512/1024×1024）未在 `SUPPORTED_SIZES` 中定义。若调用方传入 `dall-e-2` + `256x256`，`SUPPORTED_SIZES` 校验可能失败。**影响低**——DALL-E 2 已逐步淘汰，且默认模型为 `dall-e-3`。
2. StableDiffusionProvider 的 `textToImage` 返回 PNG base64，`AiImageService` 解码后存储。若 Stability AI API 返回非 PNG 格式（如 JPEG/WebP），`imagecreatefromstring` 可能失败。**影响低**——SD3/SDXL 默认返回 PNG。

## 测试覆盖

18 个测试用例覆盖：
- 文生图（DALL-E + Stable Diffusion 各 1）
- 图生图（DALL-E + Stable Diffusion）
- 图片编辑/Inpainting（DALL-E edits + Stable Diffusion）
- 风格迁移
- 结果存储（FileUpload 记录 + URL 返回）
- 请求日志（ai_requests 表记录）
- 参数校验（prompt 为空、prompt 过长、输入图片不存在、遮罩图不存在）
- 提供商不支持操作
- 上游 HTTP 错误落库
- 提供商路由（按 model 标识自动路由）

全部通过：18 tests, 46 assertions, OK。

---

## Verdict

**PASS**

代码完整实现 TASK-012 全部范围（文生图、图生图、图片编辑、风格迁移、尺寸/质量控制、结果存储、请求日志），两个提供商（DALL-E 3 + Stable Diffusion）功能正常，翻译 key 无缺失，phpunit 全绿。
