<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use MultiTenantSaas\Context\TenantContext;
use MultiTenantSaas\Middleware\IdentifyDomain;

class TestController extends Controller
{
    public function index(Request $request)
    {
        $domainType = TenantContext::getDomainType();
        $tenantId = TenantContext::getId();
        $tenant = TenantContext::getTenant();
        $tenantRole = TenantContext::getTenantRole();

        return response()->json([
            'message' => 'Multi-Tenant SaaS 测试页面',
            'domain_type' => $domainType,
            'host' => $request->header('X-Original-Host') ?? $request->getHost(),
            'path' => $request->getPathInfo(),
            'tenant_id' => $tenantId,
            'tenant_name' => $tenant?->name,
            'tenant_role' => $tenantRole,
            'user' => $request->user()?->name ?? '未登录',
        ]);
    }

    public function console(Request $request)
    {
        $domainType = TenantContext::getDomainType();
        $tenantId = TenantContext::getId();
        $tenant = TenantContext::getTenant();
        $tenantRole = TenantContext::getTenantRole();

        return response()->json([
            'message' => '租户管理后台',
            'domain_type' => $domainType,
            'host' => $request->header('X-Original-Host') ?? $request->getHost(),
            'path' => $request->getPathInfo(),
            'tenant_id' => $tenantId,
            'tenant_name' => $tenant?->name,
            'tenant_role' => $tenantRole,
            'user' => $request->user()?->name ?? '未登录',
            'access' => in_array($tenantRole, ['super_admin', 'tenant_admin']) ? '允许' : '拒绝',
        ]);
    }

    public function admin(Request $request)
    {
        $domainType = TenantContext::getDomainType();

        return response()->json([
            'message' => '系统管理后台',
            'domain_type' => $domainType,
            'host' => $request->header('X-Original-Host') ?? $request->getHost(),
            'path' => $request->getPathInfo(),
            'user' => $request->user()?->name ?? '未登录',
        ]);
    }
}
