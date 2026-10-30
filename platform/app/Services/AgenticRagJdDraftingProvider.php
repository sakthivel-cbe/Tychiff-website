<?php

namespace App\Services;

use App\AI\Agents\JdPreparationAgent;
use App\Contracts\JdDraftingProvider;
use App\Exceptions\JdDraftingUnavailableException;
use App\Models\Requirement;
use App\Models\User;

class AgenticRagJdDraftingProvider implements JdDraftingProvider
{
    public function __construct(private readonly JdPreparationAgent $agent) {}

    public function generate(array $requirement): array
    {
        $requirementId = $requirement['_context']['requirement_id'] ?? null;
        $userId = $requirement['_context']['user_id'] ?? null;
        $record = is_numeric($requirementId) ? Requirement::query()->find($requirementId) : null;
        $user = is_numeric($userId) ? User::query()->find($userId) : null;
        if (! $record || ! $user) {
            throw new JdDraftingUnavailableException('AI drafting context is unavailable. Continue with manual JD preparation.');
        }

        $result = $this->agent->prepare($user, $record);
        if (! $result->available || $result->data === null) {
            throw new JdDraftingUnavailableException('AI drafting is temporarily unavailable. Continue with manual JD preparation.');
        }

        return $result->data;
    }
}
