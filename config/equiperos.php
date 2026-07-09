<?php

return [

    // Event Backbone
    'event_stream' => env('REDIS_EVENT_STREAM', 'equiper:events'),

    // Salla Anti-Corruption Layer
    'salla' => [
        'api_base_url' => env('SALLA_API_BASE_URL', 'https://api.salla.dev'),
        'client_id' => env('SALLA_CLIENT_ID'),
        'client_secret' => env('SALLA_CLIENT_SECRET'),
        'webhook_secret' => env('SALLA_WEBHOOK_SECRET'),
        'reconciliation_interval_minutes' => env('SALLA_RECONCILIATION_INTERVAL_MINUTES', 30),
    ],

    // AI Reasoning Service
    'ai' => [
        'anthropic_api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
        // Loop-detection guardrail (AI Operating Core doc, Section 8):
        // one generation attempt per Product per enrichment event, no silent retries.
        'max_generations_per_product_per_event' => env('AI_MAX_GENERATIONS_PER_PRODUCT_PER_EVENT', 1),
        'monthly_cost_ceiling_usd' => env('AI_MONTHLY_COST_CEILING_USD', 200),
    ],

    // Roles seeded at organization creation (matches PRD F2)
    'default_roles' => ['owner', 'marketing_manager', 'seo_specialist', 'designer'],

];
