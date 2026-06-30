<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ToolLogResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MultiTenantSaas\Contracts\AgentMonitorContract;
use MultiTenantSaas\Contracts\AgentServiceContract;
use MultiTenantSaas\Contracts\TenantContextContract;
use MultiTenantSaas\Models\AgentToolLog;

/**
 * Agent 监控 API（§6.3）
 *
 * 提供 Agent 的使用统计、Token 用量、成本估算、工具调用日志端点。
 * 所有操作强制租户隔离，tenant_id 从 TenantContext 解析（认证中间件设置）。
 *
 * 端点：
 *  GET  /api/v1/agents/{id}/stats       使用统计
 *  GET  /api/v1/agents/{id}/token-usage  Token 用量
 *  GET  /api/v1/agents/{id}/cost         成本估算
 *  GET  /api/v1/agents/{id}/tool-logs    工具调用日志
 */
class AgentStatsController extends Controller
{
    public function __construct(
        private AgentMonitorContract $monitor,
        private AgentServiceContract $agentService,
        private TenantContextContract $tenantContext,
    ) {}

    /**
     * 获取 Agent 使用统计（§6.3）
     *
     * 返回指定时间范围内的会话数、工具调用数、平均响应时间、成功率等指标。
     *
     * @queryParam start_date string 开始日期（Y-m-d）
     * @queryParam end_date   string 结束日期（Y-m-d）
     */
    public function stats(Request $request, int $agentId): JsonResponse
    {
        $this->validateAgentOwnership($agentId);

        $startDate = $request->query('start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = $request->query('end_date', date('Y-m-d'));

        $metrics = $this->monitor->getPerformanceMetrics($agentId, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * 获取 Agent Token 用量统计（§6.3）
     *
     * 返回指定时间范围内的 prompt_tokens、completion_tokens、total_tokens 汇总。
     *
     * @queryParam start_date string 开始日期（Y-m-d）
     * @queryParam end_date   string 结束日期（Y-m-d）
     */
    public function tokenUsage(Request $request, int $agentId): JsonResponse
    {
        $this->validateAgentOwnership($agentId);

        $startDate = $request->query('start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = $request->query('end_date', date('Y-m-d'));

        $usage = $this->monitor->getTokenUsage($agentId, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $usage,
        ]);
    }

    /**
     * 获取 Agent 成本估算（§6.3）
     *
     * 根据 Token 用量和模型定价估算指定时间范围内的成本。
     *
     * @queryParam start_date string 开始日期（Y-m-d）
     * @queryParam end_date   string 结束日期（Y-m-d）
     */
    public function cost(Request $request, int $agentId): JsonResponse
    {
        $this->validateAgentOwnership($agentId);

        $startDate = $request->query('start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = $request->query('end_date', date('Y-m-d'));

        $cost = $this->monitor->getCostEstimate($agentId, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => [
                'agent_id' => $agentId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'estimated_cost' => $cost,
            ],
        ]);
    }

    /**
     * 获取 Agent 工具调用日志（§6.3）
     *
     * 返回分页的工具调用日志列表，按创建时间倒序排列。
     */
    public function toolLogs(Request $request, int $agentId): JsonResponse
    {
        $tenantId = $this->resolveTenantId();
        $this->validateAgentOwnership($agentId);

        $query = AgentToolLog::where('agent_id', $agentId)
            ->orderBy('created_at', 'desc');

        // 通过 agent_conversations 表关联过滤当前租户的会话
        $query->whereIn('conversation_id', function ($subQuery) use ($agentId, $tenantId) {
            $subQuery->select('conversation_id')
                ->from('agent_conversations')
                ->where('agent_id', $agentId)
                ->where('tenant_id', $tenantId);
        });

        $perPage = min((int) $request->query('per_page', 20), 100);
        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ToolLogResource::collection($logs),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * 校验 Agent 存在且属于当前租户
     */
    private function validateAgentOwnership(int $agentId): void
    {
        $tenantId = $this->resolveTenantId();
        $agent = $this->agentService->find($agentId);

        if ($agent === null || (int) $agent->tenant_id !== $tenantId) {
            abort(404, 'Agent 不存在或不属于当前租户');
        }
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
