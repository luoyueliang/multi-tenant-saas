<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesTenantAccess;
use App\Http\Resources\TenantUserResource;
use Illuminate\Http\Request;
use MultiTenantSaas\Models\TenantUser;
use MultiTenantSaas\Services\AuditService;

class TenantMemberController extends Controller
{
    use AuthorizesTenantAccess;

    public function index(Request $request, int $tenantId)
    {
        $this->ensureTenantAccess($request, $tenantId);

        $members = TenantUser::where('tenant_id', $tenantId)
            ->join('users', 'users.user_id', '=', 'tenant_users.user_id')
            ->select(
                'users.user_id', 'users.name', 'users.email',
                'tenant_users.role', 'tenant_users.is_active', 'tenant_users.joined_at'
            )
            ->get();

        return response()->json(['success' => true, 'data' => TenantUserResource::collection($members)]);
    }

    public function store(Request $request, int $tenantId)
    {
        $this->ensureTenantAccess($request, $tenantId);

        $request->validate([
            'user_id' => 'required',
            'role' => 'in:tenant_admin,end_user',
        ]);

        TenantUser::updateOrCreate(
            ['tenant_id' => $tenantId, 'user_id' => $request->user_id],
            ['role' => $request->role ?? 'end_user', 'is_active' => true, 'joined_at' => now()]
        );

        AuditService::log('create', 'tenant_user', $request->user_id, null, [
            'tenant_id' => $tenantId,
            'role' => $request->role ?? 'end_user',
        ]);

        return response()->json(['success' => true, 'message' => '成员已添加']);
    }

    public function update(Request $request, int $tenantId, int $userId)
    {
        $this->ensureTenantAccess($request, $tenantId);

        $member = TenantUser::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $oldValues = ['role' => $member->role, 'is_active' => $member->is_active];
        $member->update($request->only(['role', 'is_active']));
        $newValues = $request->only(['role', 'is_active']);

        AuditService::log('update', 'tenant_user', $userId, $oldValues, $newValues);

        return response()->json(['success' => true, 'message' => '已更新']);
    }
}
