<?php

namespace App\Contracts;

interface JdDraftingProvider
{
    /** @return array<string, string|null> */
    public function generate(array $requirement): array;
}
