<?php

return [
    // Only the real reverse proxy addresses / CIDRs, never every visitor.
    'proxies' => array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', ''))))),
];
