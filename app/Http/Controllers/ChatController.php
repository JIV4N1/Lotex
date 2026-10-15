<?php

namespace App\Http\Controllers;

use App\Services\ChatCatalog;
use App\Services\GeminiChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ChatController extends Controller
{
    public function __invoke(Request $request, GeminiChat $gemini, ChatCatalog $catalog)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:500'],
            'history' => ['sometimes', 'array', 'max:6'],
            'history.*' => ['required', 'string', 'max:500'],
            'property_slug' => ['nullable', 'string', 'max:200'],
        ]);
        if (! config('chat.key')) {
            return response()->json(['message' => 'El asistente aún no está configurado. Puedes usar el catálogo mientras tanto.'], 503);
        }
        // Shared file lock makes the daily ceiling atomic even for concurrent visitors.
        try {
            $allowed = Cache::lock('chat-budget-lock', 5)->block(2, function () {
                $key = 'chat-budget-'.now()->format('Y-m-d');
                $used = Cache::get($key, 0);
                if ($used >= config('chat.daily_limit')) {
                    return false;
                }
                Cache::put($key, $used + 1, now()->endOfDay());

                return true;
            });
            if (! $allowed) {
                return response()->json(['message' => 'Alcanzamos el límite diario del asistente. Puedes continuar en el catálogo.'], 429);
            }
            $filters = $gemini->interpret([...($data['history'] ?? []), $data['message']], ! empty($data['property_slug']));

            return response()->json($catalog->answer($filters, $data['property_slug'] ?? null))->header('Cache-Control', 'no-store');
        } catch (Throwable $exception) {
            // Do not log chat messages, API keys or raw provider/database errors.
            return response()->json(['message' => 'No pude consultar el asistente en este momento. Inténtalo más tarde o explora el catálogo.'], 503);
        }
    }
}
