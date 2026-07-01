<?php

namespace MultiTenantSaas\Services\Channel;

use MultiTenantSaas\Contracts\ChannelContract;

class ChannelManager
{
    protected array $channels = [];

    public function register(string $name, ChannelContract $channel): void
    {
        $this->channels[$name] = $channel;
    }

    public function get(string $name): ?ChannelContract
    {
        return $this->channels[$name] ?? null;
    }

    public function all(): array
    {
        return $this->channels;
    }

    public function has(string $name): bool
    {
        return isset($this->channels[$name]);
    }
}
