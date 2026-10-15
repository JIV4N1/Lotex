<x-filament-panels::page>
    @php($summary = $this->overview())
    <section class="hz-welcome" aria-labelledby="chat-overview-title">
        <div><span class="hz-eyebrow">ASISTENTE HORIZONTE</span><h2 id="chat-overview-title">Entiende qué buscan tus visitantes</h2><p>Revisa las preguntas, las respuestas y el uso de Gemini desde un solo lugar.</p></div>
        <a class="hz-action" href="https://aistudio.google.com/usage" target="_blank" rel="noopener noreferrer">Uso en Google AI Studio ↗</a>
    </section>
    <section aria-label="Resumen general del chatbot" class="hz-stats">
        <article class="hz-stat"><h2>Consultas registradas</h2><strong>{{ number_format($summary['requests']) }}</strong><p>Últimos {{ config('chat.retention_days') }} días</p></article>
        <article class="hz-stat"><h2>Tokens reportados</h2><strong>{{ number_format($summary['tokens']) }}</strong><p>{{ $summary['measured'] }} de {{ $summary['requests'] }} consultas con consumo conocido</p></article>
        <article class="hz-stat"><h2>Respuestas fallidas</h2><strong>{{ number_format($summary['errors']) }}</strong><p>Últimos {{ config('chat.retention_days') }} días</p></article>
        <article class="hz-stat"><h2>Límite local de hoy</h2><strong>{{ $summary['used'] }} / {{ $summary['limit'] }}</strong><p>Intentos permitidos · reinicio 00:00 {{ config('app.timezone') }}</p><progress class="hz-usage-progress" aria-label="Uso del límite diario local" value="{{ min($summary['used'], $summary['limit']) }}" max="{{ max(1, $summary['limit']) }}"></progress></article>
    </section>
    <div class="hz-chat-note"><p>Este resumen registra llamadas del chatbot de esta web desde que se activó el registro. No equivale a la factura ni a la cuota total de Google. Los tokens ausentes se muestran como «No reportados», no como cero.</p><p>Los filtros siguientes afectan únicamente a la lista. Las preguntas y respuestas están cifradas en la base y solo son visibles para administradores. No se recopilan IP ni identificadores del visitante; el texto puede contener datos que él haya escrito.</p></div>
    {{ $this->table }}
</x-filament-panels::page>
