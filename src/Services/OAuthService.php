<?php

namespace MultiTenantSaas\Services;

use MultiTenantSaas\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Overtrue\Socialite\Contracts\UserInterface as SocialiteUser;
use Overtrue\Socialite\SocialiteManager;

/**
 * OAuth 社会化登录服务
 *
 * 凭证来源规则：
 *   wechat      → 全局 .env / config/services.php（公共平台通用）
 *   wechat_work → 租户 settings.oauth.wechat_work（每家企业单独配置）
 *   feishu      → 租户 settings.oauth.feishu
 *   dingtalk    → 租户 settings.oauth.dingtalk
 */
class OAuthService
{
    private const STATE_CACHE_PREFIX = 'oauth_state:';

    private const STATE_TTL_MINUTES = 10;

    /** 路由 provider 名 → overtrue/socialite driver 名 */
    private const PROVIDER_MAP = [
        'wechat' => 'wechat',
        'wechat_work' => 'wework',
        'feishu' => 'feishu',
        'dingtalk' => 'dingtalk',
    ];

    /** 需要从租户 settings 读取凭证的企业级 provider */
    private const ENTERPRISE_PROVIDERS = ['wechat_work', 'feishu', 'dingtalk'];

    /**
     * 生成 OAuth 授权跳转 URL 并将 state 存入缓存。
     */
    public function buildAuthUrl(string $provider, string $redirectUri, ?int $tenantId = null): string
    {
        $state = Str::random(32);

        Cache::put(
            self::STATE_CACHE_PREFIX.$state,
            ['redirect_uri' => $redirectUri, 'tenant_id' => $tenantId],
            now()->addMinutes(self::STATE_TTL_MINUTES)
        );

        $driver = $this->makeSocialite($provider, $tenantId)->create(self::PROVIDER_MAP[$provider]);

        return $driver->withState($state)->redirect();
    }

    /**
     * 处理 OAuth 回调，返回 socialite 用户及前端跳转信息。
     *
     * @return array{user: SocialiteUser, redirect_uri: string, tenant_id: int|null}
     *
     * @throws \RuntimeException state 无效或已过期
     */
    public function handleCallback(string $provider, string $code, string $state): array
    {
        $cached = Cache::pull(self::STATE_CACHE_PREFIX.$state);

        if (! $cached) {
            throw new \RuntimeException('OAuth state 无效或已过期，请重新发起授权');
        }

        $tenantId = $cached['tenant_id'] ?? null;

        $socialiteUser = $this->makeSocialite($provider, $tenantId)
            ->create(self::PROVIDER_MAP[$provider])
            ->userFromCode($code);

        return [
            'user' => $socialiteUser,
            'redirect_uri' => $cached['redirect_uri'],
            'tenant_id' => $tenantId,
        ];
    }

    /**
     * 将 SocialiteUser 转换为 UserService::loginViaOauth 所需的 oauthData 数组。
     */
    public function toOauthData(string $provider, SocialiteUser $user): array
    {
        $raw = $user->getRaw();

        // 微信 PC 扫码登录优先使用 unionid 作为 provider_id（跨应用唯一标识）
        if ($provider === 'wechat' && ! empty($raw['unionid'])) {
            $resolvedProvider = 'wechat_unionid';
            $resolvedId = (string) $raw['unionid'];
        } else {
            $resolvedProvider = $provider;
            $resolvedId = (string) $user->getId();
        }

        return [
            'provider' => $resolvedProvider,
            'provider_id' => $resolvedId,
            'provider_name' => $user->getNickname() ?: $user->getName(),
            'provider_email' => $user->getEmail(),
            'provider_avatar' => $user->getAvatar(),
            'access_token' => $user->getAccessToken(),
            'refresh_token' => $user->getRefreshToken(),
            'token_expires_at' => $user->getExpiresIn()
                ? now()->addSeconds((int) $user->getExpiresIn())
                : null,
            'metadata' => $raw,
        ];
    }

    /**
     * 构建仅含指定 provider 配置的 SocialiteManager。
     * 企业级 provider（wechat_work / feishu / dingtalk）从租户 settings 读取凭证；
     * wechat（公共平台）从全局 config/services.php 读取。
     */
    private function makeSocialite(string $provider, ?int $tenantId = null): SocialiteManager
    {
        $socialiteKey = self::PROVIDER_MAP[$provider];

        if (in_array($provider, self::ENTERPRISE_PROVIDERS, true) && $tenantId !== null) {
            $config = $this->getTenantOauthConfig($provider, $tenantId);
        } else {
            $configKey = ($provider === 'wechat_work') ? 'wechat_work' : $provider;
            $config = config("services.{$configKey}", []);
        }

        return new SocialiteManager([$socialiteKey => $config]);
    }

    /**
     * 从租户 settings.oauth.{provider} 读取 OAuth 凭证。
     *
     * settings 中存储格式（以 wechat_work 为例）：
     * {
     *   "oauth": {
     *     "wechat_work": {
     *       "corp_id": "ww8224b6d91b6ea3f3",
     *       "agent_id": "1000002",
     *       "secret":   "ZBeSI60HPL-eKaP1...",
     *       "redirect": "https://ai.mtedu.com/api/v1/auth/wechat_work/callback"
     *     }
     *   }
     * }
     *
     * @throws \RuntimeException 租户未配置该 OAuth provider
     */
    private function getTenantOauthConfig(string $provider, int $tenantId): array
    {
        $tenant = Tenant::query()->select(['tenant_id', 'settings'])->find($tenantId);

        if (! $tenant) {
            throw new \RuntimeException("租户 {$tenantId} 不存在");
        }

        $config = data_get($tenant->settings, "oauth.{$provider}");

        if (empty($config)) {
            throw new \RuntimeException("租户 {$tenantId} 尚未配置 {$provider} 登录");
        }

        // overtrue/socialite 统一使用 client_id / client_secret 字段名
        return array_merge($config, [
            'client_id' => $config['corp_id'] ?? $config['app_id'] ?? $config['client_id'] ?? '',
            'client_secret' => $config['secret'] ?? $config['app_secret'] ?? $config['client_secret'] ?? '',
        ]);
    }
}
