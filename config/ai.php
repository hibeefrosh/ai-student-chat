<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Provider
    |--------------------------------------------------------------------------
    |
    | This value determines which AI provider to use for generating responses.
    | Supported: "openai", "gemini"
    |
    */
    'provider' => env('AI_PROVIDER', 'gemini'),

    /*
    |--------------------------------------------------------------------------
    | OpenAI Configuration
    |--------------------------------------------------------------------------
    |
    | These are the configuration options for the OpenAI API.
    |
    */
    'openai_api_key' => env('OPENAI_API_KEY'),
    'openai_model' => env('OPENAI_MODEL', 'gpt-4'),
    'openai_temperature' => env('OPENAI_TEMPERATURE', 0.7),
    'openai_max_tokens' => env('OPENAI_MAX_TOKENS', 2000),
    'openai_embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-ada-002'),

    /*
    |--------------------------------------------------------------------------
    | Google Gemini Configuration
    |--------------------------------------------------------------------------
    |
    | These are the configuration options for the Google Gemini API.
    |
    */
    'gemini_api_key' => env('GEMINI_API_KEY'),
    'gemini_model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    'gemini_temperature' => env('GEMINI_TEMPERATURE', 0.7),
    'gemini_max_tokens' => env('GEMINI_MAX_TOKENS', 2000),

    /*
    |--------------------------------------------------------------------------
    | Chunking Configuration
    |--------------------------------------------------------------------------
    |
    | These are the configuration options for chunking text documents.
    |
    */
    'chunk_size' => env('CHUNK_SIZE', 1000),
    'chunk_overlap' => env('CHUNK_OVERLAP', 200),
];