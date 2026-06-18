<?php

namespace MultiTenantSaas\Services;

use MultiTenantSaas\Models\CreditAccount;
use MultiTenantSaas\Models\Tenant;
use MultiTenantSaas\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    public function __construct(
        private IdGenerator $idGenerator,
        private MteduLegacyService $mteduLegacy
    ) {}

    /**
     * 获取用户列表（带分页和筛选）
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = User::query()->with(['tenants']);

        // 搜索（name 或 email）
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // 按角色筛选
        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        // 按租户筛选
        if (! empty($filters['tenant_id'])) {
            $query->whereHas('tenants', function ($q) use ($filters) {
                $q->where('tenants.tenant_id', $filters['tenant_id']);
            });
        }

        // 按邮箱验证状态筛选
        if (isset($filters['email_verified'])) {
            if ($filters['email_verified']) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        // 排序
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        // 分页
        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * 注册公共平台用户（自动附加到平台默认租户，并赠送欢迎积分）
     *
     * @param  array{name: string, email?: string, phone?: string, password?: string, avatar?: string}  $data
     */
    public function registerAsPlatformUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'password' => isset($data['password']) ? Hash::make($data['password']) : null,
                'avatar' => $data['avatar'] ?? null,
                'role' => 'platform_user',
            ]);

            $platformTenantId = (int) config('id.platform_tenant_id');
            $this->attachToTenant($user->user_id, $platformTenantId, 'end_user');
            $this->giveWelcomeCredits($user->user_id, $platformTenantId);
            $this->autoClaimDefaultTemplate($user, $platformTenantId);

            // 手机注册时检查是否为馒头老用户
            if (! empty($data['phone'])) {
                $this->markAsMteduLegacyIfExists($user, $data['phone'], $platformTenantId);
            }

            return $user->fresh();
        });
    }

    /**
     * 注册为企业租户用户（直接挂到目标租户，不经过公共租户）
     *
     * 调用前须确保：
     *  - 平台已授权该租户开放注册 (allow_open_registration)
     *  - 租户自身已开启注册 (allow_register)
     *  - 邮箱域名白名单已校验通过
     *
     * @param  array{name: string, email?: string, phone?: string, password?: string, avatar?: string}  $data
     * @param  int  $tenantId  目标企业租户 ID
     * @param  int  $welcomeCredits  欢迎积分数
     */
    public function registerAsTenantUser(array $data, int $tenantId, int $welcomeCredits = 0): User
    {
        return DB::transaction(function () use ($data, $tenantId, $welcomeCredits) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'password' => isset($data['password']) ? Hash::make($data['password']) : null,
                'avatar' => $data['avatar'] ?? null,
                'role' => 'platform_user',
            ]);

            // 直接挂到企业租户
            $this->attachToTenant($user->user_id, $tenantId, 'end_user');

            // 赠送欢迎积分
            if ($welcomeCredits > 0) {
                $account = CreditAccount::where('tenant_id', $tenantId)
                    ->where('user_id', $user->user_id)
                    ->first();
                if ($account) {
                    $account->recharge($user->user_id, $welcomeCredits, '新用户注册赠送积分', ['source' => 'welcome_bonus']);
                }
            }

            return $user->fresh();
        });
    }

    /**
     * 通过 OAuth 登录（找或新建用户，并确保租户附件）
     *
     * @param  array{provider: string, provider_id: string, provider_name?: string, provider_email?: string, provider_avatar?: string, access_token?: string, refresh_token?: string, token_expires_at?: \Carbon\Carbon|null, metadata?: array}  $oauthData
     */
    public function loginViaOauth(array $oauthData, int $tenantId, string $tenantRole = 'end_user'): User
    {
        return DB::transaction(function () use ($oauthData, $tenantId, $tenantRole) {
            $oauthAccount = \MultiTenantSaas\Models\OauthAccount::where('provider', $oauthData['provider'])
                ->where('provider_id', $oauthData['provider_id'])
                ->first();

            $isNewUser = false;

            if ($oauthAccount) {
                // 已有 oauth 账号：更新令牌信息，返回关联用户
                $oauthAccount->update([
                    'access_token' => $oauthData['access_token'] ?? $oauthAccount->access_token,
                    'refresh_token' => $oauthData['refresh_token'] ?? $oauthAccount->refresh_token,
                    'token_expires_at' => $oauthData['token_expires_at'] ?? $oauthAccount->token_expires_at,
                    'provider_name' => $oauthData['provider_name'] ?? $oauthAccount->provider_name,
                    'provider_avatar' => $oauthData['provider_avatar'] ?? $oauthAccount->provider_avatar,
                    'metadata' => $oauthData['metadata'] ?? $oauthAccount->metadata,
                ]);

                $user = $oauthAccount->user;
            } else {
                $isNewUser = true;
                // 首次 OAuth 登录：新建用户 + OAuth 账号
                $name = $oauthData['provider_name'] ?? ('用户'.substr($oauthData['provider_id'], -4));
                $email = $oauthData['provider_email'] ?? null;

                if ($email && User::where('email', $email)->exists()) {
                    $email = null;
                }
                // email 不能为 null，若 OAuth 未提供邮箱则生成占位地址
                if (! $email) {
                    $email = $oauthData['provider'].'_'.strtolower(substr($oauthData['provider_id'], 0, 20)).'@oauth.local';
                }
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'avatar' => $oauthData['provider_avatar'] ?? null,
                    'role' => 'platform_user',
                    'password' => Hash::make(Str::random(32)),
                ]);

                \MultiTenantSaas\Models\OauthAccount::create([
                    'user_id' => $user->user_id,
                    'tenant_id' => $tenantId,
                    'provider' => $oauthData['provider'],
                    'provider_id' => $oauthData['provider_id'],
                    'provider_email' => $oauthData['provider_email'] ?? null,
                    'provider_name' => $oauthData['provider_name'] ?? null,
                    'provider_avatar' => $oauthData['provider_avatar'] ?? null,
                    'access_token' => $oauthData['access_token'] ?? null,
                    'refresh_token' => $oauthData['refresh_token'] ?? null,
                    'token_expires_at' => $oauthData['token_expires_at'] ?? null,
                    'metadata' => $oauthData['metadata'] ?? null,
                ]);
            }

            // 确保用户已关联目标租户；首次关联时赠送欢迎积分
            if (! $user->tenants()->where('tenants.tenant_id', $tenantId)->exists()) {
                $this->attachToTenant($user->user_id, $tenantId, $tenantRole);
                if ($isNewUser) {
                    $this->giveWelcomeCredits($user->user_id, $tenantId);
                }
            }

            return $user->fresh();
        });
    }

    /**
     * 赠送欢迎积分（新用户首次加入租户时）
     * 积分数量由 config('id.platform_welcome_credits') 控制，设为 0 则跳过。
     */
    private function giveWelcomeCredits(int $userId, int $tenantId): void
    {
        $credits = (int) config('id.platform_welcome_credits', 500);
        if ($credits <= 0) {
            return;
        }

        $account = CreditAccount::firstOrCreate(
            ['tenant_id' => $tenantId, 'user_id' => $userId, 'account_type' => 'personal'],
            ['balance' => 0, 'gift_balance' => 0, 'recharge_balance' => 0, 'total_recharged' => 0, 'total_consumed' => 0, 'status' => 'active']
        );

        $account->gift($userId, $credits, 30, '新用户注册赠送积分', ['source' => 'welcome_bonus']);
    }

    /**
     * 自动认领默认数字员工模板（公众号助理）
     */
    private function autoClaimDefaultTemplate(User $user, int $tenantId): void
    {
        $defaultSlug = config('opc.default_employee_template', 'assistant');

        $template = \MultiTenantSaas\Models\DigitalEmployeeTemplate::where('slug', $defaultSlug)
            ->where('status', 'active')
            ->where('is_public', true)
            ->first();

        if (! $template) {
            return;
        }

        \MultiTenantSaas\Models\UserEmployee::firstOrCreate(
            ['user_id' => $user->user_id, 'template_id' => $template->template_id],
            [
                'tenant_id' => $tenantId,
                'slug' => $template->slug,
                'claimed_at' => now(),
                'status' => 'active',
            ]
        );
    }

    /**
     * 在用户验证手机号后，尝试认领馒头商学院老账号（mtedu 迁移奖励）。
     *
     * 防重机制：`mtedu_import_records.mtedu_legacy_id` 有 UNIQUE 约束，
     * 同一个 mtedu 账号只能被认领一次，即使被不同平台用户尝试也无法重复。
     *
     * @return array{claimed: bool, credits: int} claimed 表示本次是否首次认领，credits 为本次赠送积分数
     */
    public function claimMteduLegacy(User $user, string $phone, int $tenantId): array
    {
        // 该用户已认领过，幂等返回
        if (\MultiTenantSaas\Models\MteduImportRecord::where('user_id', $user->user_id)->exists()) {
            return ['claimed' => false, 'credits' => 0];
        }

        $legacyUser = $this->mteduLegacy->findByPhone($phone);
        if (! $legacyUser) {
            return ['claimed' => false, 'credits' => 0];
        }

        $legacyId = (string) $legacyUser['id'];

        // 该 mtedu 账号已被其他平台用户认领，跳过（防止多账号薅羊毛）
        if (\MultiTenantSaas\Models\MteduImportRecord::where('mtedu_legacy_id', $legacyId)->exists()) {
            return ['claimed' => false, 'credits' => 0];
        }

        $mteduCredits = (int) config('id.platform_mtedu_credits', 200);

        DB::transaction(function () use ($user, $tenantId, $legacyId, $legacyUser, $phone, $mteduCredits) {
            // 写入导入记录（UNIQUE 约束保障并发安全）
            \MultiTenantSaas\Models\MteduImportRecord::create([
                'user_id' => $user->user_id,
                'mtedu_legacy_id' => $legacyId,
                'mtedu_phone' => $phone,
                'credits_granted' => $mteduCredits,
            ]);

            // 写入 OAuth 账号绑定（让用户能看到绑定来源）
            if (! \MultiTenantSaas\Models\OauthAccount::where('user_id', $user->user_id)->where('provider', 'mtedu_legacy')->exists()) {
                \MultiTenantSaas\Models\OauthAccount::create([
                    'user_id' => $user->user_id,
                    'tenant_id' => $tenantId,
                    'provider' => 'mtedu_legacy',
                    'provider_id' => $legacyId,
                    'provider_name' => $legacyUser['name'] ?? null,
                    'metadata' => ['source' => 'mtedu_legacy'],
                ]);
            }

            // 赠送迁移积分
            if ($mteduCredits > 0) {
                $account = CreditAccount::where('tenant_id', $tenantId)->where('user_id', $user->user_id)->first();
                if ($account) {
                    $account->recharge($user->user_id, $mteduCredits, '馒头商学院老用户迁移积分', ['source' => 'mtedu_migration']);
                }
            }
        });

        return [
            'claimed' => true,
            'credits' => $mteduCredits,
            'legacy_name' => $legacyUser['name'] ?? null,
            'legacy_avatar' => $legacyUser['avatar'] ?? $legacyUser['headimgurl'] ?? null,
        ];
    }

    /**
     * 若该手机号在馒头商学院旧库中存在，则为用户打上 mtedu_legacy 标签。
     * 旧库不可用时静默跳过，不影响主流程。
     *
     * @internal 注册时调用；手机绑定时请用 claimMteduLegacy() 以获取返回值
     */
    private function markAsMteduLegacyIfExists(User $user, string $phone, int $tenantId): void
    {
        $this->claimMteduLegacy($user, $phone, $tenantId);
    }

    /**
     * 馒头商学院老用户导入登录。
     *
     * mtedu.com 将用户信息通过签名 Token 传递过来，本方法：
     *  - 若 mtedu_legacy 账号已存在：直接返回关联用户（幂等）
     *  - 若手机/邮箱已在本平台注册：关联 mtedu_legacy，确保归入默认租户
     *  - 若完全新用户：创建帐号，赠送欢迎积分
     *
     * @param  array{uid: string, name: string, phone?: string, email?: string}  $mteduData
     */
    public function loginFromMteduLegacy(array $mteduData): User
    {
        return DB::transaction(function () use ($mteduData) {
            // 幂等检查：已经导入过
            $existing = \MultiTenantSaas\Models\OauthAccount::where('provider', 'mtedu_legacy')
                ->where('provider_id', (string) $mteduData['uid'])
                ->first();

            if ($existing) {
                return $existing->user;
            }

            // 通过手机或邮箱查找已有账号
            $user = null;
            if (! empty($mteduData['phone'])) {
                $user = User::where('phone', $mteduData['phone'])->first();
            }

            if (! $user && ! empty($mteduData['email'])) {
                $user = User::where('email', $mteduData['email'])->first();
            }

            $platformTenantId = (int) config('id.platform_tenant_id');
            $isFirstJoin = false;

            if (! $user) {
                // 全新用户
                $isFirstJoin = true;
                $email = $mteduData['email'] ?? null;
                if (! $email) {
                    $email = 'mtedu_'.$mteduData['uid'].'@mtedu.local';
                }

                $user = User::create([
                    'name' => $mteduData['name'],
                    'phone' => $mteduData['phone'] ?? null,
                    'email' => $email,
                    'password' => Hash::make(Str::random(32)),
                    'role' => 'platform_user',
                ]);
            }

            // 确保归入平台租户
            if (! $user->tenants()->where('tenants.tenant_id', $platformTenantId)->exists()) {
                $this->attachToTenant($user->user_id, $platformTenantId, 'end_user');
                $isFirstJoin = true;
            }

            // 首次加入平台则赠送欢迎积分
            if ($isFirstJoin) {
                $this->giveWelcomeCredits($user->user_id, $platformTenantId);
            }

            // 记录 mtedu_legacy 绑定
            \MultiTenantSaas\Models\OauthAccount::create([
                'user_id' => $user->user_id,
                'tenant_id' => $platformTenantId,
                'provider' => 'mtedu_legacy',
                'provider_id' => (string) $mteduData['uid'],
                'provider_email' => $mteduData['email'] ?? null,
                'provider_name' => $mteduData['name'] ?? null,
                'metadata' => ['source' => 'mtedu_legacy'],
            ]);

            return $user->fresh();
        });
    }

    /**
     * 通过微信 UnionID 查找或创建本平台用户（wechatRelay 分支 2 调用）。
     *
     * 适用场景：pass.mtedu.com 的 weixintmp 中有该微信记录，但该微信账号
     * 尚未绑定手机号（type=0），pass 只返回 mt_oid（UnionID），无 mt_uid。
     *
     * @param  string  $unionId  微信 UnionID（跨应用唯一）
     * @param  string|null  $nickname  pass 的 mt_uname cookie（URL-decoded）
     */
    public function loginFromWechatUnionId(string $unionId, ?string $nickname = null): User
    {
        return DB::transaction(function () use ($unionId, $nickname) {
            // 幂等检查：已有 wechat_unionid 绑定
            $existing = \MultiTenantSaas\Models\OauthAccount::where('provider', 'wechat_unionid')
                ->where('provider_id', $unionId)
                ->first();

            if ($existing) {
                return $existing->user;
            }

            $platformTenantId = (int) config('id.platform_tenant_id');

            // 全新用户，用占位邮箱创建（后续绑定手机时可补全）
            $email = 'wx_'.substr(md5($unionId), 0, 12).'@wechat.local';
            $user = User::create([
                'name' => $nickname ?? ('微信用户'.substr($unionId, -4)),
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'role' => 'platform_user',
            ]);

            $this->attachToTenant($user->user_id, $platformTenantId, 'end_user');
            $this->giveWelcomeCredits($user->user_id, $platformTenantId);

            // 记录 UnionID 绑定，后续绑定手机后可通过 claimMteduLegacy 认领
            \MultiTenantSaas\Models\OauthAccount::create([
                'user_id' => $user->user_id,
                'tenant_id' => $platformTenantId,
                'provider' => 'wechat_unionid',
                'provider_id' => $unionId,
                'provider_name' => $nickname,
                'metadata' => ['source' => 'wechat_relay_unbound'],
            ]);

            return $user->fresh();
        });
    }

    /**
     * 创建用户
     */
    public function create(array $data): User
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => isset($data['password']) ? Hash::make($data['password']) : null,
                'avatar' => $data['avatar'] ?? null,
                'role' => $data['role'] ?? 'platform_user',
                'email_verified_at' => $data['email_verified'] ?? false ? now() : null,
            ]);

            // 如果指定了租户，关联租户
            if (! empty($data['tenant_id'])) {
                $this->attachToTenant($user->user_id, $data['tenant_id'], $data['tenant_role'] ?? 'end_user');
            }

            DB::commit();

            return $user->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 更新用户
     */
    public function update(int $userId, array $data): User
    {
        DB::beginTransaction();
        try {
            $user = User::findOrFail($userId);

            $updateData = [
                'name' => $data['name'] ?? $user->name,
                'email' => $data['email'] ?? $user->email,
                'phone' => $data['phone'] ?? $user->phone,
                'avatar' => $data['avatar'] ?? $user->avatar,
                'role' => $data['role'] ?? $user->role,
            ];

            // 如果提供了新密码，更新密码
            if (! empty($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }

            // 如果提供了邮箱验证状态
            if (isset($data['email_verified'])) {
                $updateData['email_verified_at'] = $data['email_verified'] ? now() : null;
            }

            $user->update($updateData);

            DB::commit();

            return $user->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 删除用户（软删除）
     */
    public function delete(int $userId): bool
    {
        DB::beginTransaction();
        try {
            $user = User::findOrFail($userId);
            $result = $user->delete();

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 查找用户
     */
    public function find(int $userId): User
    {
        return User::with(['tenants', 'creditAccounts', 'oauthAccounts'])->findOrFail($userId);
    }

    /**
     * 将用户关联到租户
     */
    public function attachToTenant(int $userId, int $tenantId, string $role = 'end_user', int $credits = 0): void
    {
        $user = User::findOrFail($userId);
        $tenant = Tenant::findOrFail($tenantId);

        // 检查是否已关联
        if ($user->tenants()->where('tenants.tenant_id', $tenantId)->exists()) {
            // 如果已关联，更新角色
            $user->tenants()->updateExistingPivot($tenantId, [
                'role' => $role,
                'credits' => $credits,
                'is_active' => true,
            ]);
        } else {
            // 新增关联（tenant_user_id 需手动生成，BelongsToMany::attach 不走 HasGlobalId）
            $user->tenants()->attach($tenantId, [
                'tenant_user_id' => $this->idGenerator->generate(),
                'role' => $role,
                'credits' => $credits,
                'is_active' => true,
                'joined_at' => now(),
            ]);
        }

        // 确保用户在此租户有个人积分账户
        CreditAccount::firstOrCreate(
            ['tenant_id' => $tenantId, 'user_id' => $userId],
            [
                'account_type' => 'personal',
                'balance' => 0,
                'total_recharged' => 0,
                'total_consumed' => 0,
            ]
        );
    }

    /**
     * 从租户移除用户
     */
    public function detachFromTenant(int $userId, int $tenantId): void
    {
        $user = User::findOrFail($userId);
        $user->tenants()->detach($tenantId);
    }

    /**
     * 更新用户在租户中的角色
     */
    public function updateTenantRole(int $userId, int $tenantId, string $role): void
    {
        $user = User::findOrFail($userId);

        $user->tenants()->updateExistingPivot($tenantId, [
            'role' => $role,
        ]);
    }

    /**
     * 获取用户的租户列表
     */
    public function getUserTenants(int $userId): Collection
    {
        $user = User::findOrFail($userId);

        return $user->tenants()
            ->withPivot('role', 'credits', 'is_active', 'joined_at')
            ->orderBy('tenant_users.joined_at', 'desc')
            ->get();
    }

    /**
     * 重置用户密码
     */
    public function resetPassword(int $userId, string $newPassword): User
    {
        $user = User::findOrFail($userId);

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        return $user->fresh();
    }

    /**
     * 启用/禁用用户
     */
    public function toggleStatus(int $userId, bool $isActive): User
    {
        $user = User::findOrFail($userId);

        if ($isActive && $user->trashed()) {
            $user->restore();
        } elseif (! $isActive && ! $user->trashed()) {
            $user->delete();
        }

        return $user->fresh();
    }

    /**
     * 获取用户统计信息
     */
    public function getUserStats(int $userId): array
    {
        $user = User::findOrFail($userId);

        return [
            'total_tenants' => $user->tenants()->count(),
            'total_tasks' => $user->tasks()->count(),
            'total_agents_created' => $user->createdAgents()->count(),
            'total_credits' => $user->creditAccounts()->sum('balance'),
            'oauth_connections' => $user->oauthAccounts()->count(),
        ];
    }
}
