<?php

namespace MultiTenantSaas\DTOs;

readonly class PromptContext
{
    public function __construct(
        public string $systemPrompt,
        public ?string $userProfile = null,
        public ?string $memoryContext = null,
        public array $metadata = [],
    ) {}

    public function isEmpty(): bool
    {
        return $this->systemPrompt === ''
            && $this->userProfile === null
            && $this->memoryContext === null;
    }
}
