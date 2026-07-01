<?php

namespace MultiTenantSaas\Services\Capability;

use MultiTenantSaas\Contracts\CapabilityContract;

class CapabilityRegistry
{
    protected array $capabilities = [];

    public function register(CapabilityContract $capability): void
    {
        $this->capabilities[$capability->name()] = $capability;
    }

    public function get(string $name): ?CapabilityContract
    {
        return $this->capabilities[$name] ?? null;
    }

    public function all(): array
    {
        return $this->capabilities;
    }

    public function has(string $name): bool
    {
        return isset($this->capabilities[$name]);
    }
}
