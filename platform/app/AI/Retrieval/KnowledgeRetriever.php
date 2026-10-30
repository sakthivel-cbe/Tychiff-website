<?php

namespace App\AI\Retrieval;

use App\AI\Data\Evidence;
use App\Models\AiKnowledgeItem;
use App\Models\User;
use Illuminate\Support\Str;

class KnowledgeRetriever
{
    public function retrieve(User $user, string $query, int $limit = 5): array
    {
        $terms = collect(preg_split('/\W+/u', mb_strtolower($query)) ?: [])
            ->filter(fn (string $term) => mb_strlen($term) >= 3)
            ->unique()->take(8)->values();
        if ($terms->isEmpty()) {
            return [];
        }

        $items = AiKnowledgeItem::query()->where('approval_status', 'approved')->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('effective_at')->orWhere('effective_at', '<=', now()))
            ->where(function ($query) use ($terms): void {
                foreach ($terms as $term) {
                    $query->orWhere('title', 'like', '%'.$term.'%')->orWhere('content', 'like', '%'.$term.'%');
                }
            })->limit(min(max($limit, 1), 10))->get();

        return $items->map(fn (AiKnowledgeItem $item) => new Evidence(
            'knowledge-'.$item->getKey().'-v'.Str::slug($item->version), 'approved_knowledge', (string) $item->getKey(),
            'content', mb_substr($item->content, 0, 10000), 'Approved active knowledge; '.$item->source_type.'; version '.$item->version.'.',
        ))->all();
    }
}
