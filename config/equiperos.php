<?php

return [

    // Event Backbone
    'event_stream' => env('REDIS_EVENT_STREAM', 'equiper:events'),

    // Salla Anti-Corruption Layer
    'salla' => [
        'api_base_url' => env('SALLA_API_BASE_URL', 'https://api.salla.dev/admin/v2'),
        'token_url' => env('SALLA_TOKEN_URL', 'https://accounts.salla.sa/oauth2/token'),
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

    // Social Media Hub — WhatsApp Business Cloud API (Phase 2)
    'whatsapp' => [
        'api_base_url' => env('WHATSAPP_API_BASE_URL', 'https://graph.facebook.com/v23.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
    ],

    // Social Media Hub — Meta Graph API: Instagram + Facebook (Phase 3)
    // Going live against real accounts requires Meta App Review —
    // see the Social Media Hub plan for what that entails.
    'meta' => [
        'api_base_url' => env('META_API_BASE_URL', 'https://graph.facebook.com/v23.0'),
        'ig_user_id' => env('META_IG_USER_ID'),
        'page_id' => env('META_PAGE_ID'),
        'access_token' => env('META_ACCESS_TOKEN'),
        'verify_token' => env('META_VERIFY_TOKEN'),
        'app_secret' => env('META_APP_SECRET'),
    ],

    // Social Media Hub — TikTok Content Posting API (Phase 4).
    // Publish-only: TikTok has no public API for reading/replying to
    // comments — see TikTokPublisher's doc comment.
    'tiktok' => [
        'api_base_url' => env('TIKTOK_API_BASE_URL', 'https://open.tiktokapis.com/v2'),
        'access_token' => env('TIKTOK_ACCESS_TOKEN'),
        'privacy_level' => env('TIKTOK_PRIVACY_LEVEL', 'PUBLIC_TO_EVERYONE'),
    ],

    // Social Media Hub — X (Twitter) API v2 (Phase 5).
    // COST WARNING: no free tier as of 2026 — every publish and every
    // mentions poll bills the connected account. Confirmed accepted by
    // the business owner before this was built.
    'x' => [
        'api_base_url' => env('X_API_BASE_URL', 'https://api.x.com/2'),
        'token_url' => env('X_TOKEN_URL', 'https://api.x.com/2/oauth2/token'),
        'client_id' => env('X_CLIENT_ID'),
        'user_id' => env('X_USER_ID'),
        'access_token' => env('X_ACCESS_TOKEN'),
        'refresh_token' => env('X_REFRESH_TOKEN'),
        'mentions_poll_interval_minutes' => env('X_MENTIONS_POLL_INTERVAL_MINUTES', 30),
    ],

];
