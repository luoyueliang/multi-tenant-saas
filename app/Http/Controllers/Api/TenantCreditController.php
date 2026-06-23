<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use MultiTenantSaas\Models\CreditAccount;
use MultiTenantSaas\Models\CreditTransaction;

class TenantCreditController extends Controller
{
    public function index(Request $request, int $tenantId)
    {
        if ($error = $this->ensureTenantAccess($request, $tenantId)) {
            return $error;
        }

        $account = CreditAccount::where('tenant_id', $tenantId)->whereNull('user_id')->first();
        $transactions = CreditTransaction::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => [
                    'total' => $account?->total_earned ?? 0,
                    'used' => $account?->total_spent ?? 0,
                    'available' => $account?->balance ?? 0,
                ],
                'transactions' => $transactions,
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
