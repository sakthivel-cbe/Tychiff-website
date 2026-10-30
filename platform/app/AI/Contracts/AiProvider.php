<?php

namespace App\AI\Contracts;

interface AiProvider
{
    public function name(): string;

    public function model(): string;

    /** @param array<string, mixed> $schema */
    public function generateStructured(string $systemPrompt, string $userPrompt, array $schema): array;
}
