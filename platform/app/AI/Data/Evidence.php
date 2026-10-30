<?php

namespace App\AI\Data;

use Illuminate\Support\Str;

class Evidence
{
    public readonly string $providerReference;

    public function __construct(
        public readonly string $id,
        public readonly string $sourceType,
        public readonly string $sourceId,
        public readonly string $field,
        public readonly string $content,
        public readonly string $reason,
        ?string $providerReference = null,
    ) {
        $this->providerReference = $providerReference ?? 'EV-'.Str::ulid();
    }

    public function promptRecord(): array
    {
        return [
            'evidence_id' => $this->providerReference,
            'source_type' => $this->sourceType,
            'field' => $this->field,
            'content' => mb_substr($this->content, 0, 4000),
            'retrieval_reason' => $this->reason,
        ];
    }
}
