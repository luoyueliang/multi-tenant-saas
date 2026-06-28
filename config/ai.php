<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI 网关全局配置
    |--------------------------------------------------------------------------
    |
    | 默认提供商、默认模型、API 版本与流式输出总开关。
    |
    */

    'default_provider' => env('AI_DEFAULT_PROVIDER', 'openai'),

    'default_model' => env('AI_DEFAULT_MODEL', 'gpt-4o-mini'),

    'api_version' => env('AI_API_VERSION', 'v1'),

    'streaming_enabled' => (bool) env('AI_STREAMING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | 超时与重试策略
    |--------------------------------------------------------------------------
    */

    'timeout' => (int) env('AI_TIMEOUT', 30),

    'retry' => [
        'attempts' => (int) env('AI_RETRY_ATTEMPTS', 2),
        'delay_ms' => (int) env('AI_RETRY_DELAY_MS', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | 速率限制
    |--------------------------------------------------------------------------
    */

    'rate_limit' => [
        'enabled' => env('AI_RATE_LIMIT_ENABLED', false),
        'max_requests_per_minute' => (int) env('AI_RATE_LIMIT_RPM', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | 提供商配置
    |--------------------------------------------------------------------------
    |
    | 各提供商从 config('ai.providers.{provider}.*') 读取自身配置。
    | 至少需包含 openai 与 zhipu，对应 OpenAiProvider / ZhipuProvider。
    |
    */

    'providers' => [
        'openai' => [
            'api_key' => env('AI_OPENAI_API_KEY', env('OPENAI_API_KEY', '')),
            'base_url' => env('AI_OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'timeout' => (int) env('AI_OPENAI_TIMEOUT', 30),
        ],

        'zhipu' => [
            'api_key' => env('AI_ZHIPU_API_KEY', env('ZHIPU_API_KEY', '')),
            'base_url' => env('AI_ZHIPU_BASE_URL', 'https://open.bigmodel.cn/api/paas/v4'),
            'timeout' => (int) env('AI_ZHIPU_TIMEOUT', 30),
        ],

        'anthropic' => [
            'api_key' => env('AI_ANTHROPIC_API_KEY', env('ANTHROPIC_API_KEY', '')),
            'base_url' => env('AI_ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
            'timeout' => (int) env('AI_ANTHROPIC_TIMEOUT', 30),
        ],

        'deepseek' => [
            'api_key' => env('AI_DEEPSEEK_API_KEY', env('DEEPSEEK_API_KEY', '')),
            'base_url' => env('AI_DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
            'timeout' => (int) env('AI_DEEPSEEK_TIMEOUT', 30),
        ],

        'stability' => [
            'api_key' => env('AI_STABILITY_API_KEY', env('STABILITY_API_KEY', '')),
            'base_url' => env('AI_STABILITY_BASE_URL', 'https://api.stability.ai/v2beta'),
            'timeout' => (int) env('AI_STABILITY_TIMEOUT', 60),
        ],

        'runway' => [
            'api_key' => env('AI_RUNWAY_API_KEY', env('RUNWAY_API_KEY', '')),
            'base_url' => env('AI_RUNWAY_BASE_URL', 'https://api.dev.runwayml.com/v1'),
            'timeout' => (int) env('AI_RUNWAY_TIMEOUT', 60),
        ],

        'kuaishou' => [
            'api_key' => env('AI_KUAISHOU_API_KEY', env('KUAISHOU_API_KEY', '')),
            'base_url' => env('AI_KUAISHOU_BASE_URL', 'https://api.kuaishou.com/v1'),
            'timeout' => (int) env('AI_KUAISHOU_TIMEOUT', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 文本 AI 配置
    |--------------------------------------------------------------------------
    |
    | AiTextService 默认模型与行为参数。各方法可显式传入模型标识覆盖默认值。
    |
    */

    'text' => [
        'default_chat_model' => env('AI_TEXT_CHAT_MODEL', 'gpt-4o-mini'),
        'default_completion_model' => env('AI_TEXT_COMPLETION_MODEL', 'gpt-4o-mini'),
        'default_embedding_model' => env('AI_TEXT_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'json_mode_enabled' => (bool) env('AI_TEXT_JSON_MODE', true),
        'default_temperature' => (float) env('AI_TEXT_TEMPERATURE', 0.7),
        'default_max_tokens' => (int) env('AI_TEXT_MAX_TOKENS', 2048),
        'max_input_length' => (int) env('AI_TEXT_MAX_INPUT_LENGTH', 16000),
    ],

    /*
    |--------------------------------------------------------------------------
    | 图片 AI 配置
    |--------------------------------------------------------------------------
    |
    | AiImageService 默认提供商、模型、尺寸、质量、风格与结果存储参数。
    | 各方法可显式传入 options 覆盖默认值。提供商 HTTP 配置复用
    | ai.providers.openai（DALL-E）与 ai.providers.stability（Stable Diffusion）。
    |
    */

    'image' => [
        'default_provider' => env('AI_IMAGE_DEFAULT_PROVIDER', 'dalle'),
        'default_model' => env('AI_IMAGE_DEFAULT_MODEL', 'dall-e-3'),
        'default_size' => env('AI_IMAGE_DEFAULT_SIZE', '1024x1024'),
        'default_quality' => env('AI_IMAGE_DEFAULT_QUALITY', 'standard'),
        'default_style' => env('AI_IMAGE_DEFAULT_STYLE', 'vivid'),
        'default_n' => (int) env('AI_IMAGE_DEFAULT_N', 1),
        'default_steps' => (int) env('AI_IMAGE_DEFAULT_STEPS', 30),
        'default_cfg_scale' => (float) env('AI_IMAGE_DEFAULT_CFG_SCALE', 7.0),
        'max_prompt_length' => (int) env('AI_IMAGE_MAX_PROMPT_LENGTH', 4000),
        'storage_category' => env('AI_IMAGE_STORAGE_CATEGORY', 'ai_generated'),
        'storage_disk' => env('AI_IMAGE_STORAGE_DISK'),
        'storage_is_public' => (bool) env('AI_IMAGE_STORAGE_PUBLIC', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | 日志配置
    |--------------------------------------------------------------------------
    */

    'log' => [
        'enable' => true,
        'file' => storage_path('logs/ai.log'),
    ],
];
