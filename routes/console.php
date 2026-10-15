<?php

use App\Services\GeminiChat;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune', ['--model' => [\App\Models\ChatInteraction::class]])->daily()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('gemini:check {--models : Lista modelos disponibles para esta clave}', function () {
    if (! config('chat.key')) {
        $this->error('Falta GEMINI_API_KEY en .env.');

        return 1;
    }
    try {
        if ($this->option('models')) {
            $response = \Illuminate\Support\Facades\Http::withHeaders(['x-goog-api-key' => config('chat.key')])->connectTimeout(5)->timeout(20)->get('https://generativelanguage.googleapis.com/v1beta/models');
            if (! $response->successful()) {
                $this->error('No se pudo consultar modelos. HTTP '.$response->status());
                return 1;
            }
            foreach ($response->json('models', []) as $model) {
                if (in_array('generateContent', $model['supportedGenerationMethods'] ?? []) && preg_match('/^models\/gemini-[a-z0-9.\-]+$/', $model['name'] ?? '')) {
                    $this->line($model['name']);
                }
            }
            return 0;
        }
        $result = app(GeminiChat::class)->interpret(['Busco terrenos de hasta 1000000 pesos en Tehuacán']);
        if ($result['intent'] !== 'search' || ($result['max_price'] ?? null) != 1000000) {
            $this->error('Gemini respondió, pero no interpretó correctamente la comprobación.');

            return 1;
        }
        $this->info('Gemini conectado: respuesta estructurada y presupuesto verificados.');

        return 0;
    } catch (Throwable $exception) {
        if ($exception instanceof \Illuminate\Http\Client\ConnectionException) {
            $this->error(str_contains($exception->getMessage(), 'cURL error 60') ? 'PHP no pudo validar el certificado TLS. Configura curl.cainfo con un archivo CA confiable; no desactives TLS.' : 'No se pudo establecer conexión HTTPS con Google (red o timeout).');
        } elseif ($exception->getCode() >= 400 && $exception->getCode() <= 599) {
            $this->error('Estado HTTP de Google: '.$exception->getCode().' (400: configuración/clave; 403: permisos; 404: modelo; 429: cuota).');
        }
        $this->error('No se pudo verificar Gemini. Revisa clave, modelo, cuota y conexión. No se muestran credenciales ni errores privados.');

        return 1;
    }
})->purpose('Comprueba Gemini con una consulta genérica sin enviar datos del catálogo');
