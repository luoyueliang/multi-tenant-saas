<?php

namespace MultiTenantSaas\Contracts;

use MultiTenantSaas\Models\Capability\CapabilityResult;

interface CapabilityContract
{
    public function name(): string;
    public function execute(array $input): CapabilityResult;
}
