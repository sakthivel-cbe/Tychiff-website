<?php

return [
    'provider' => env('AI_PROVIDER', 'openai'),
    'fallback_enabled' => (bool) env('AI_FALLBACK_ENABLED', true),
    'candidate_data_processing_enabled' => (bool) env('AI_CANDIDATE_DATA_PROCESSING_ENABLED', false),
    'timeout' => (int) env('AI_TIMEOUT', 45),
    'documents' => [
        'pdf_to_text_binary' => env('PDFTOTEXT_BINARY', 'pdftotext'),
        'extraction_timeout' => (int) env('DOCUMENT_EXTRACTION_TIMEOUT', 90),
    ],
    'test_workflow' => [
        'enabled' => (bool) env('AI_TEST_WORKFLOW_ENABLED', false),
        'live_inference_enabled' => (bool) env('AI_TEST_LIVE_INFERENCE_ENABLED', false),
    ],
    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY', ''),
            'model' => env('OPENAI_MODEL', ''),
            'endpoint' => env('OPENAI_API_ENDPOINT', 'https://api.openai.com/v1/chat/completions'),
        ],
        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY', ''),
            'model' => env('ANTHROPIC_MODEL', ''),
            'endpoint' => env('ANTHROPIC_API_ENDPOINT', 'https://api.anthropic.com/v1/messages'),
            'api_version' => env('ANTHROPIC_API_VERSION', '2023-06-01'),
            'max_tokens' => (int) env('ANTHROPIC_MAX_TOKENS', 4096),
        ],
    ],
];
