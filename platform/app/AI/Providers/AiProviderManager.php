<?php

namespace App\AI\Providers;

use App\AI\Contracts\AiProvider;

class AiProviderManager
{
    /** @return array{providers: list<AiProvider>, unavailable_preference: ?string} */
    public function plan(): array
    {
        $preferred = config('ai.provider') === 'anthropic' ? 'anthropic' : 'openai';
        $other = $preferred === 'openai' ? 'anthropic' : 'openai';
        $preferredProvider = $this->make($preferred);
        $otherProvider = $this->make($other);

        if ($preferredProvider !== null) {
            return [
                'providers' => array_values(array_filter([
                    $preferredProvider,
                    config('ai.fallback_enabled') ? $otherProvider : null,
                ])),
                'unavailable_preference' => null,
            ];
        }

        return [
            'providers' => $otherProvider !== null ? [$otherProvider] : [],
            'unavailable_preference' => $preferred,
        ];
    }

    public function make(string $name): ?AiProvider
    {
        $settings = config('ai.providers.'.$name, []);
        if (! is_string($settings['api_key'] ?? null) || trim($settings['api_key']) === ''
            || ! is_string($settings['model'] ?? null) || trim($settings['model']) === '') {
            return null;
        }

        if ($name === 'openai') {
            return new OpenAiProvider(
                $settings['api_key'], $settings['model'], $settings['endpoint'], (int) config('ai.timeout'),
            );
        }
        if ($name === 'anthropic') {
            return new AnthropicProvider(
                $settings['api_key'], $settings['model'], $settings['endpoint'], $settings['api_version'],
                (int) $settings['max_tokens'], (int) config('ai.timeout'),
            );
        }

        return null;
    }
}
