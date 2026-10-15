<?php

return [
    'key' => env('GEMINI_API_KEY'),
    'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
    'daily_limit' => (int) env('CHAT_DAILY_LIMIT', 100),
];
