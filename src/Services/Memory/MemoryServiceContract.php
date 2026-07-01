<?php

namespace MultiTenantSaas\Services\Memory;

interface MemoryServiceContract
{
    public function read(string $entityType, int $entityId, string $key): mixed;
    public function write(string $entityType, int $entityId, string $key, mixed $value): void;
    public function compress(string $entityType, int $entityId): void;
    public function decay(float $threshold = 0.1): void;
}
