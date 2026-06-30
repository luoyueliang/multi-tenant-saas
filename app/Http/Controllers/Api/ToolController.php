<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\RegisterToolRequest;
use App\Http\Requests\Agent\UpdateToolRequest;
use App\Http\Resources\ToolResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MultiTenantSaas\Contracts\TenantContextContract;
use MultiTenantSaas\Contracts\ToolRegistryContract;
use MultiTenantSaas\Models\AgentTool;
use MultiTenantSaas\Scopes\TenantScope;

/**
 * 工具管理 API（§6.4）
 *
 * 提供工具的列表、详情、注册、更新、删除端点。
 * 工具分两级：全局工具（tenant_id=0）和租户私有工具。
 *
 * 端点：
 *  GET    /api/v1/tools          所有可用工具
 *  GET    /api/v1/tools/{slug}   工具详情
 *  POST   /api/v1/tools          注册新工具
 *  PUT    /api/v1/tools/{slug}   更新工具
 *  DELETE /api/v1/tools/{slug}   删除工具
 */
class ToolController extends Controller
{
    public function __construct(
        private ToolRegistryContract $toolRegistry,
        private TenantContextContract $tenantContext,
    ) {}

    /**
     * 获取当前租户可用的所有工具（§6.4）
     *
     * 包含全局工具（tenant_id=0）和当前租户私有工具。
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId();

        $tools = AgentTool::withoutGlobalScope(TenantScope::class)
            ->where('enabled', true)
            ->where(function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)
                    ->orWhere('tenant_id', 0);
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => ToolResource::collection($tools),
        ]);
    }

    /**
     * 获取指定工具详情（§6.4）
     *
     * 按 slug 查询，返回当前租户可见的工具（全局或私有）。
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $tenantId = $this->resolveTenantId();

        $tool = AgentTool::withoutGlobalScope(TenantScope::class)
            ->where('slug', $slug)
            ->where(function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)
                    ->orWhere('tenant_id', 0);
            })
            ->first();

        if ($tool === null) {
            return response()->json([
                'success' => false,
                'message' => '工具不存在或不属于当前租户',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ToolResource($tool),
        ]);
    }

    /**
     * 注册新工具（§6.4）
     *
     * 同时写入数据库（持久化）和注册表（运行时可用）。
     */
    public function store(RegisterToolRequest $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId();

        $tool = AgentTool::create([
            'tenant_id' => $tenantId,
            'name' => $request->validated('name'),
            'slug' => $request->validated('slug'),
            'description' => $request->validated('description'),
            'category' => $request->input('category'),
            'parameters_schema' => $request->validated('parameters_schema'),
            'handler_class' => $request->validated('handler_class'),
            'enabled' => $request->boolean('enabled', true),
        ]);

        // 同步注册到运行时注册表
        $this->toolRegistry->register(
            $tool->slug,
            $tool->handler_class,
            $tool->parameters_schema
        );

        return response()->json([
            'success' => true,
            'message' => '工具注册成功',
            'data' => new ToolResource($tool),
        ], 201);
    }

    /**
     * 更新工具（§6.4）
     *
     * 仅允许更新当前租户私有的工具，全局工具不可修改。
     */
    public function update(UpdateToolRequest $request, string $slug): JsonResponse
    {
        $tenantId = $this->resolveTenantId();

        $tool = AgentTool::withoutGlobalScope(TenantScope::class)
            ->where('slug', $slug)
            ->where('tenant_id', $tenantId) // 仅限租户私有工具
            ->first();

        if ($tool === null) {
            return response()->json([
                'success' => false,
                'message' => '工具不存在或不属于当前租户',
            ], 404);
        }

        $tool->update(array_filter([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'category' => $request->input('category'),
            'parameters_schema' => $request->input('parameters_schema'),
            'handler_class' => $request->input('handler_class'),
            'enabled' => $request->input('enabled'),
        ], fn ($value) => $value !== null));

        return response()->json([
            'success' => true,
            'message' => '工具更新成功',
            'data' => new ToolResource($tool->fresh()),
        ]);
    }

    /**
     * 删除工具（§6.4）
     *
     * 仅允许删除当前租户私有的工具，全局工具不可删除。
     */
    public function destroy(Request $request, string $slug): JsonResponse
    {
        $tenantId = $this->resolveTenantId();

        $tool = AgentTool::withoutGlobalScope(TenantScope::class)
            ->where('slug', $slug)
            ->where('tenant_id', $tenantId) // 仅限租户私有工具
            ->first();

        if ($tool === null) {
            return response()->json([
                'success' => false,
                'message' => '工具不存在或不属于当前租户',
            ], 404);
        }

        $tool->delete();

        return response()->json([
            'success' => true,
            'message' => '工具已删除',
        ]);
    }

    /**
     * 从 TenantContext 解析当前租户 ID
     */
    private function resolveTenantId(): int
    {
        $tenantId = $this->tenantContext->resolveId();

        if ($tenantId === null) {
            abort(403, '无法识别当前租户');
        }

        return (int) $tenantId;
    }
}
