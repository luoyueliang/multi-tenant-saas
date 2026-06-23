<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use MultiTenantSaas\Models\AuditLog;

class TenantAuditController extends Controller
{
    public function index(Request $request, int $tenantId)
    {
        if ($error = $this->ensureTenantAccess($request, $tenantId)) {
            return $error;
        }

        $query = AuditLog::where('tenant_id', $tenantId)->orderBy('created_at', 'desc');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('resource_type')) {
            $query->where('resource_type', $request->resource_type);
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
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
