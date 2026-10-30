<?php

namespace App\AI\Services;

use App\Models\Candidate;

class CandidateTextRedactor
{
    public function redact(string $text, Candidate $candidate): string
    {
        $privateValues = array_filter([
            $candidate->full_name,
            $candidate->email,
            $candidate->alternate_contact,
            $candidate->mobile,
            $candidate->mobile_normalized,
        ], fn ($value) => is_string($value) && trim($value) !== '');
        foreach (array_unique($privateValues) as $value) {
            $text = str_ireplace($value, '[redacted personal detail]', $text);
        }

        $text = preg_replace('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i', '[redacted email]', $text) ?? $text;

        return preg_replace('/(?<!\d)\+?(?:\d[\s().-]*){8,15}(?!\d)/u', '[redacted phone]', $text) ?? $text;
    }
}
