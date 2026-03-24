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

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'recommendation_limit' => env('AI_RECOMMENDATION_LIMIT', 5),
        'default_recommendation_prompt' => env(
            'AI_DEFAULT_RECOMMENDATION_PROMPT',
            'Score each plate on:\n'
            . '1. Dietary Compatibility (40%): Does the plate match the user\'s dietary tags? Higher score if no conflicts.\n'
            . '2. Nutritional Balance (40%): Is the plate healthy based on ingredients? Prioritize variety, proteins, veggies, low sugar if requested.\n'
            . '3. Price Reasonableness (20%): Is the price fair? Lower is better.\n'
            . 'Calculate final score = (dietary_score * 0.4) + (nutritional_score * 0.4) + (price_score * 0.2).\n'
            . 'Sort plates from highest to lowest final score. Return the top recommendations.'
        ),
    ],

];
