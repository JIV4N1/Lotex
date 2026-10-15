<?php

namespace App\Filament\Resources\ChatInteractionResource\Pages;

use App\Filament\Resources\ChatInteractionResource;
use App\Models\ChatInteraction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListChatInteractions extends ListRecords
{
    protected static string $resource = ChatInteractionResource::class;

    protected string $view = 'filament.chatbot.index';

    public function overview(): array
    {
        $stats = ChatInteraction::retained()->selectRaw("COUNT(*) AS requests, COUNT(total_tokens) AS measured, COALESCE(SUM(total_tokens), 0) AS tokens, COUNT(*) FILTER (WHERE status = 'error') AS errors")->first();

        return [
            'requests' => (int) $stats->requests, 'tokens' => (int) $stats->tokens,
            'measured' => (int) $stats->measured, 'errors' => (int) $stats->errors,
            'used' => (int) Cache::get('chat-budget-'.now()->format('Y-m-d'), 0),
            'limit' => max(0, config('chat.daily_limit')),
        ];
    }
}
