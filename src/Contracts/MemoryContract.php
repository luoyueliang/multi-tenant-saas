<?php

namespace MultiTenantSaas\Contracts;

interface MemoryContract
{
    public function read(string $key): mixed;
    public function write(string $key, mixed $value): void;
    public function compress(): void;
    public function decay(): void;
}
