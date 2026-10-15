<?php

namespace App\Services;

use App\Models\ChatInteraction;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatTelemetry
{
    public function record(array $data, array $usage, string $status, ?string $answer, int $duration): void
    {
        try {
            // Do not persist history, credentials, IP addresses or raw provider responses.
            ChatInteraction::create([
                'question' => $data['message'], 'answer' => $answer,
                'property_slug' => $data['property_slug'] ?? null,
                'model' => mb_substr(config('chat.model'), 0, 150), 'status' => $status,
                'http_status' => $usage['http_status'] ?? null,
                'input_tokens' => $usage['input_tokens'] ?? null,
                'output_tokens' => $usage['output_tokens'] ?? null,
                'total_tokens' => $usage['total_tokens'] ?? null,
                'duration_ms' => min($duration, 2147483647),
            ]);
        } catch (Throwable) {
            // A monitoring outage must not break a valid answer or expose sensitive errors.
            Log::warning('No se pudo guardar la métrica del chatbot. Revisar almacenamiento y migraciones.');
        }
    }
}
