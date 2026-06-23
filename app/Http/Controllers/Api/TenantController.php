<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use MultiTenantSaas\Models\Tenant;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->role !== 'super_admin') {
            return response()->json(['success' => false, 'message' => '无权限'], 403);
        }

        $tenants = Tenant::paginate(15);

        return response()->json([
            'success' => true,
            'data' => $tenants->items(),
            'meta' => [
                'current_page' => $tenants->currentPage(),
                'last_page' => $tenants->lastPage(),
                'per_page' => $tenants->perPage(),
                'total' => $tenants->total(),
            ],
        ]);
    }

    public function show(Request $request, int $tenantId)
    {
        if ($request->user()->role !== 'super_admin') {
            return response()->json(['success' => false, 'message' => '无权限'], 403);
        }

        $tenant = Tenant::findOrFail($tenantId);

        return response()->json(['success' => true, 'data' => $tenant]);
    }

    public function update(Request $request, int $tenantId)
    {
        if ($request->user()->role !== 'super_admin') {
            return response()->json(['success' => false, 'message' => '无权限'], 403);
        }

        $tenant = Tenant::findOrFail($tenantId);
        $tenant->update($request->only([
            'name', 'status', 'subscription_plan', 'custom_domain',
            'description', 'contact_name', 'contact_email', 'contact_phone',
        ]));

        return response()->json(['success' => true, 'data' => $tenant]);
    }

    public function destroy(Request $request, int $tenantId)
    {
        if ($request->user()->role !== 'super_admin') {
            return response()->json(['success' => false, 'message' => '无权限'], 403);
        }

        Tenant::findOrFail($tenantId)->delete();

        return response()->json(['success' => true, 'message' => '已删除']);
    }
}
