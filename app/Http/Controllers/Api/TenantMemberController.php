<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use MultiTenantSaas\Models\TenantUser;

class TenantMemberController extends Controller
{
    protected function ensureTenantAccess(Request $request, int $tenantId)
    {
        $user = $request->user();

        if ($user->role === 'super_admin') {
            return response()->json(['success' => false, 'message' => '系统管理员不能访问租户数据'], 403);
        }

        $tenantUser = $user->tenants()
            ->where('tenants.tenant_id', $tenantId)
            ->wherePivot('is_active', true)
            ->first();

        if (!$tenantUser) {
            return response()->json(['success' => false, 'message' => '您不属于该租户'], 403);
        }

        return null;
    }

    public function index(Request $request, int $tenantId)
    {
        if ($error = $this->ensureTenantAccess($request, $tenantId)) {
            return $error;
        }

        $members = TenantUser::where('tenant_id', $tenantId)
            ->join('users', 'users.user_id', '=', 'tenant_users.user_id')
            ->select(
                'users.user_id', 'users.name', 'users.email',
                'tenant_users.role', 'tenant_users.is_active', 'tenant_users.joined_at'
            )
            ->get();

        return response()->json(['success' => true, 'data' => $members]);
    }

    public function store(Request $request, int $tenantId)
    {
        if ($error = $this->ensureTenantAccess($request, $tenantId)) {
            return $error;
        }

        $request->validate([
            'user_id' => 'required',
            'role' => 'in:tenant_admin,end_user',
        ]);

        TenantUser::updateOrCreate(
            ['tenant_id' => $tenantId, 'user_id' => $request->user_id],
            ['role' => $request->role ?? 'end_user', 'is_active' => true, 'joined_at' => now()]
        );

        return response()->json(['success' => true, 'message' => '成员已添加']);
    }

    public function update(Request $request, int $tenantId, int $userId)
    {
        if ($error = $this->ensureTenantAccess($request, $tenantId)) {
            return $error;
        }

        $member = TenantUser::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $member->update($request->only(['role', 'is_active']));

        return response()->json(['success' => true, 'message' => '已更新']);
    }
}
