<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use MultiTenantSaas\Modules\Domain\Services\DomainService;

class TenantDomainController extends Controller
{
    public function index(Request $request, int $tenantId)
    {
        if ($error = $this->ensureTenantAccess($request, $tenantId)) {
            return $error;
        }

        $service = new DomainService();
        return response()->json(['success' => true, 'data' => $service->getDomainInfo($tenantId)]);
    }

    public function update(Request $request, int $tenantId)
    {
        if ($error = $this->ensureTenantAccess($request, $tenantId)) {
            return $error;
        }

        $request->validate(['domain' => 'required|string']);
        $service = new DomainService();
        $service->updateDomain($tenantId, $request->domain);

        return response()->json(['success' => true, 'message' => '域名已更新，等待审核']);
    }

    public function approve(Request $request, int $tenantId)
    {
        if ($error = $this->ensureTenantAccess($request, $tenantId)) {
            return $error;
        }

        $service = new DomainService();
        $service->approveDomain($tenantId);

        return response()->json(['success' => true, 'message' => '域名已审核通过']);
    }

    public function reject(Request $request, int $tenantId)
    {
        if ($error = $this->ensureTenantAccess($request, $tenantId)) {
            return $error;
        }

        $service = new DomainService();
        $service->rejectDomain($tenantId, $request->reason ?? '');

        return response()->json(['success' => true, 'message' => '域名已拒绝']);
    }

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
}
