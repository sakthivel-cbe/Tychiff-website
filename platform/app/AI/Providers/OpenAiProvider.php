<?php

namespace App\AI\Providers;

use App\AI\Contracts\AiProvider;
use App\AI\Exceptions\AiProviderException;
use Illuminate\Support\Facades\Http;
use Throwable;

class OpenAiProvider implements AiProvider
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $modelName,
        private readonly string $endpoint,
        private readonly int $timeout,
    ) {}

    public function name(): string
    {
        return 'openai';
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
            $response = Http::acceptJson()->withToken($this->apiKey)->timeout($this->timeout)->post($this->endpoint, [
                'model' => $this->modelName,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => ['name' => 'agentic_rag_result', 'strict' => true, 'schema' => $schema],
                ],
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt."\nReturn only valid JSON conforming to the supplied response schema."],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ]);
            if (! $response->successful()) {
                throw new AiProviderException('provider_http_error');
            }

            return $this->decode($response->json('choices.0.message.content'));
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
}
