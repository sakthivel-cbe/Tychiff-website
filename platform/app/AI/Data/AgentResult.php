<?php

namespace App\AI\Data;

class AgentResult
{
    public function __construct(
        public readonly bool $available,
        public readonly ?array $data,
        public readonly array $evidence,
        public readonly ?int $executionId,
        public readonly ?string $provider,
        public readonly ?string $model,
        public readonly ?string $failureCode = null,
    ) {}
}
