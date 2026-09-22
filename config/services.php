<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'stripe' => [
        'pro_price_id' => env('STRIPE_PRO_PRICE_ID'),
        // Monthly Pro price in USD — used for admin MRR figures. Keep in sync with the Stripe price.
        'pro_monthly_usd' => (float) env('STRIPE_PRO_MONTHLY_USD', 5),
    ],

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY', ''),
    ],

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URL'),
    ],

    'apple' => [
        // Sign in with Apple (native iOS): expected `aud` of identity tokens.
        'bundle_id' => env('APPLE_BUNDLE_ID', 'com.thecashfox.app'),
    ],

    'mobile' => [
        // Accept Expo Go deep links (exp://, exps://) and the Expo Go Apple
        // audience (host.exp.Exponent). Dev only — keep false in production.
        'allow_expo_go' => (bool) env('MOBILE_ALLOW_EXPO_GO', false),
    ],

    'turnstile' => [
        'site_key'   => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

    'analytics' => [
        'ga4_id' => env('GOOGLE_ANALYTICS_ID'),
    ],

    // In-app purchases (App Store / Google Play) via RevenueCat.
    // webhook_secret: the value RevenueCat sends as "Authorization: Bearer <secret>"
    //                 (set in RevenueCat → Integrations → Webhooks).
    // secret_key:     RevenueCat secret API key (sk_...) for GET /v1/subscribers.
    // Both unset → webhook rejects everything, /billing/sync returns 503.
    'revenuecat' => [
        'webhook_secret' => env('REVENUECAT_WEBHOOK_SECRET'),
        'secret_key'     => env('REVENUECAT_SECRET_KEY'),
        'entitlement'    => env('REVENUECAT_ENTITLEMENT', 'pro'),
        // Sandbox purchases grant Pro (Apple review buys with sandbox accounts
        // against production). Sandbox monthly subs renew every ~5 min and lapse within hours.
        'allow_sandbox'  => (bool) env('REVENUECAT_ALLOW_SANDBOX', true),
    ],

];
