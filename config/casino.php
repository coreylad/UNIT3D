<?php

declare(strict_types=1);

return [
    'enabled' => env('CASINO_ENABLED', true),

    'minimum_ratio' => (float) env('CASINO_MINIMUM_RATIO', 0.40),

    'max_open_wagers_per_user' => (int) env('CASINO_MAX_OPEN_WAGERS_PER_USER', 5),

    'max_open_wagers_total' => (int) env('CASINO_MAX_OPEN_WAGERS_TOTAL', 50),

    'announce_to_chat' => (bool) env('CASINO_ANNOUNCE_TO_CHAT', false),

    'allowed_amounts' => [
        20 * 1024 * 1024 * 1024,
        50 * 1024 * 1024 * 1024,
        100 * 1024 * 1024 * 1024,
        250 * 1024 * 1024 * 1024,
        500 * 1024 * 1024 * 1024,
        1024 * 1024 * 1024 * 1024,
        2048 * 1024 * 1024 * 1024,
    ],
];