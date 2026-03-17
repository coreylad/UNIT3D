<?php

declare(strict_types=1);

return [
    'enabled' => env('CASINO_ENABLED', true),

    // Minimum staff/user group level required to access casino features.
    'minimum_group_level' => (int) env('CASINO_MINIMUM_GROUP_LEVEL', 0),

    // Optional per-user permission gate: if true, users with can_download = false cannot use casino.
    'require_can_download' => (bool) env('CASINO_REQUIRE_CAN_DOWNLOAD', true),

    'minimum_ratio' => (float) env('CASINO_MINIMUM_RATIO', 0.40),

    'max_open_wagers_per_user' => (int) env('CASINO_MAX_OPEN_WAGERS_PER_USER', 5),

    'max_open_wagers_total' => (int) env('CASINO_MAX_OPEN_WAGERS_TOTAL', 50),

    'announce_to_chat' => (bool) env('CASINO_ANNOUNCE_TO_CHAT', true),

    'bot_name' => env('CASINO_BOT_NAME', 'casinoBOT'),

    'bot_command' => env('CASINO_BOT_COMMAND', 'casinobot'),

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