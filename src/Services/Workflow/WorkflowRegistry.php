<?php

namespace MultiTenantSaas\Services\Workflow;

use MultiTenantSaas\Models\Workflow;

class WorkflowRegistry
{
    protected array $workflows = [];

    public function register(Workflow $workflow): void
    {
        $this->workflows[$workflow->name] = $workflow;
    }

    public function getByName(string $name): ?Workflow
    {
        return $this->workflows[$name] ?? null;
    }

    public function getByTenant(int $tenantId): array
    {
        return array_filter($this->workflows, fn($w) => $w->tenant_id == $tenantId);
    }

    public function all(): array
    {
        return $this->workflows;
    }
}
