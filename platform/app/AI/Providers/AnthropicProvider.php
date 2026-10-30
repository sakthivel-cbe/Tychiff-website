<?php

namespace App\AI\Providers;

use App\AI\Contracts\AiProvider;
use App\AI\Exceptions\AiProviderException;
use Illuminate\Support\Facades\Http;
use Throwable;

class AnthropicProvider implements AiProvider
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $modelName,
        private readonly string $endpoint,
        private readonly string $apiVersion,
        private readonly int $maxTokens,
        private readonly int $timeout,
    ) {}

    public function name(): string
    {
        return 'anthropic';
    }

    public function model(): string
    {
        return $this->modelName;
    }

    public function generateStructured(string $systemPrompt, string $userPrompt, array $schema): array
    {
        $endpoint = parse_url($this->endpoint);
        if (! is_array($endpoint) || ($endpoint['scheme'] ?? null) !== 'https' || empty($endpoint['host'])) {
            throw new AiProviderException('provider_endpoint_invalid');
        }
        try {
            $response = Http::acceptJson()
                ->withHeaders(['x-api-key' => $this->apiKey, 'anthropic-version' => $this->apiVersion])
                ->timeout($this->timeout)->post($this->endpoint, [
                    'model' => $this->modelName,
                    'max_tokens' => $this->maxTokens,
                    'system' => $systemPrompt,
                    'messages' => [['role' => 'user', 'content' => $userPrompt]],
                    'output_config' => [
                        'format' => ['type' => 'json_schema', 'schema' => $this->supportedSchema($schema)],
                    ],
                ]);
            if (! $response->successful()) {
                throw new AiProviderException('provider_http_error');
            }

            $text = collect($response->json('content', []))
                ->filter(fn ($block) => ($block['type'] ?? null) === 'text' && is_string($block['text'] ?? null))
                ->pluck('text')->implode("\n");

            return $this->decode($text);
        } catch (AiProviderException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new AiProviderException('provider_transport_error');
        }
    }

    private function decode(mixed $content): array
    {
        if (! is_string($content) || $content === '') {
            throw new AiProviderException('provider_empty_response');
        }
        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            throw new AiProviderException('provider_malformed_json');
        }
        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new AiProviderException('provider_malformed_json');
        }

        return $decoded;
    }

    private function supportedSchema(array $schema): array
    {
        foreach (['minimum', 'maximum', 'exclusiveMinimum', 'exclusiveMaximum', 'multipleOf', 'minLength', 'maxLength', 'patternProperties'] as $unsupported) {
            unset($schema[$unsupported]);
        }
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($schema['properties'] as $key => $property) {
                if (is_array($property)) {
                    $schema['properties'][$key] = $this->supportedSchema($property);
                }
            }
        }
        foreach (['items', 'additionalItems'] as $childKey) {
            if (isset($schema[$childKey]) && is_array($schema[$childKey])) {
                $schema[$childKey] = $this->supportedSchema($schema[$childKey]);
            }
        }
        foreach (['anyOf', 'allOf', 'oneOf'] as $unionKey) {
            if (isset($schema[$unionKey]) && is_array($schema[$unionKey])) {
                $schema[$unionKey] = array_map(fn (array $item) => $this->supportedSchema($item), $schema[$unionKey]);
            }
        }
        if (($schema['type'] ?? null) === 'object' && isset($schema['properties'])) {
            $schema['additionalProperties'] = false;
        }

        return $schema;
    }
}
