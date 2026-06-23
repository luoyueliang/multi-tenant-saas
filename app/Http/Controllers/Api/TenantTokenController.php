<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use MultiTenantSaas\Models\TenantSetting;

class TenantTokenController extends Controller
{
    public function index(Request $request, int $tenantId)
    {
        $this->ensureTenantAccess($request, $tenantId);

        $tokens = TenantSetting::where('tenant_id', $tenantId)
            ->where('group', 'api_token')
            ->get()
            ->map(function ($s) {
                $data = json_decode($s->value, true) ?? [];
                return [
                    'id' => $s->id,
                    'name' => $data['name'] ?? $s->key,
                    'created_at' => $s->created_at,
                    'last_used_at' => $data['last_used_at'] ?? null,
                    'expires_at' => $data['expires_at'] ?? null,
                ];
            });

        return response()->json(['success' => true, 'data' => $tokens]);
    }

    public function store(Request $request, int $tenantId)
    {
        $this->ensureTenantAccess($request, $tenantId);

        $request->validate(['name' => 'required|string|max:255']);

        $plainToken = Str::random(40);
        $tokenHash = hash('sha256', $plainToken);

        TenantSetting::create([
            'tenant_id' => $tenantId,
            'group' => 'api_token',
            'key' => 'token_' . substr($tokenHash, 0, 8),
            'value' => json_encode([
                'name' => $request->name,
                'token_hash' => $tokenHash,
                'expires_at' => $request->expires_at,
            ]),
        ]);

        return response()->json([
            'success' => true,
            'data' => ['name' => $request->name, 'token' => $plainToken],
        ], 201);
    }

    public function destroy(Request $request, int $tenantId, int $tokenId)
    {
        $this->ensureTenantAccess($request, $tenantId);

        TenantSetting::where('tenant_id', $tenantId)
            ->where('group', 'api_token')
            ->where('id', $tokenId)
            ->delete();

        return response()->json(['success' => true, 'message' => 'Token已删除']);
    }

    private function ensureTenantAccess(Request $request, int $tenantId): void
    {
        $user = $request->user();

        if ($user->role === 'super_admin') {
            abort(403, '系统管理员不能访问租户数据');
        }

        $tenantUser = $user->tenants()
            ->where('tenants.tenant_id', $tenantId)
            ->wherePivot('is_active', true)
            ->first();

        if (!$tenantUser) {
            abort(403, '您不属于该租户');
        }
    }
}
