<?php

namespace App\Services;

use App\Contracts\JdDraftingProvider;
use App\Exceptions\JdDraftingUnavailableException;

class UnavailableJdDraftingProvider implements JdDraftingProvider
{
    public function generate(array $requirement): array
    {
        throw new JdDraftingUnavailableException('AI drafting is not configured. You can continue with manual JD preparation.');
    }
}
