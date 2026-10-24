<div class="horizon-chat" data-chat data-endpoint="{{ route('chat') }}" data-token="{{ csrf_token() }}" data-property="{{ request()->routeIs('properties.show') ? request()->route('slug') : '' }}">
    <button class="chat-launcher" type="button" aria-label="Abrir asistente Horizonte" aria-haspopup="dialog" aria-controls="horizon-chat-dialog" hidden>
        <svg width="23" height="23" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 11.5a8 8 0 0 1-8 8H5l-3 2v-10a9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.6"/><path d="M7 10h8M7 14h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        <span>¿Te orientamos?</span>
    </button>
    <dialog id="horizon-chat-dialog" class="chat-dialog" aria-labelledby="chat-title">
        <header class="chat-header">
            <span class="chat-avatar" aria-hidden="true">H</span>
            <div><h2 id="chat-title">Asistente Horizonte</h2><span>Tu búsqueda, más fácil · IA</span></div>
            <button class="chat-close" type="button" aria-label="Cerrar asistente" autofocus>×</button>
        </header>
        <button type="button" class="chat-resize chat-resize-top" data-resize="top" aria-label="Ajustar altura del chat con las flechas arriba y abajo" title="Arrastra para ajustar la altura"></button>
        <button type="button" class="chat-resize chat-resize-left" data-resize="left" aria-label="Ajustar ancho del chat con las flechas izquierda y derecha" title="Arrastra para ajustar el ancho"></button>
        <button type="button" class="chat-resize chat-resize-corner" data-resize="corner" aria-label="Ajustar tamaño del chat con las flechas" title="Arrastra para ajustar el tamaño"><span aria-hidden="true">⌜</span></button>
        <div class="chat-messages" role="log" aria-label="Conversación con Horizonte" aria-live="polite" aria-relevant="additions" tabindex="0">
            <div class="chat-bubble chat-bot"><span class="chat-speaker">Horizonte</span>{{ request()->routeIs('properties.show') ? '¡Hola! Estoy en la ficha de este terreno. Puedes preguntarme por sus medidas, precio o servicios.' : '¡Hola! Puedo buscar terrenos por zona, presupuesto y superficie. ¿Qué estás buscando?' }}</div>
        </div>
        <div class="chat-suggestions" aria-label="Preguntas de ejemplo">
            <button type="button" data-chat-topic="terrenos">Explorar terrenos</button>
            <button type="button" data-chat-topic="documentos">Ver documentación</button>
            <button type="button" data-chat-topic="visita">Coordinar una visita</button>
        </div>
        <details class="chat-privacy">
            <summary>Consultas registradas · Privacidad</summary>
            <div>
                <p>Se envían tu pregunta y hasta seis preguntas anteriores a Google Gemini. En el nivel gratuito, Google puede usarlas para mejorar sus productos. Evita compartir datos personales.</p>
                <p>Horizonte registra cada pregunta y su respuesta para revisar la calidad del asistente. Solo los administradores pueden consultarlas durante {{ config('chat.retention_days') }} días. Evita incluir nombres, teléfonos o información privada.</p>
                <p>Al cerrar con × se borra el historial de esta pantalla, no el registro del servicio ni los datos ya enviados a Google.</p>
                <p>Este asistente con IA consulta el catálogo publicado. No ofrece atención humana, reserva visitas ni verifica la validez de documentos.</p>
                <a href="https://ai.google.dev/gemini-api/terms" target="_blank" rel="noopener noreferrer">Condiciones de Gemini ↗</a>
            </div>
        </details>
        <p class="chat-status" role="status" aria-live="polite"></p>
        <form class="chat-form">
            <label class="visually-hidden" for="chat-message">Tu pregunta sobre terrenos</label>
            <input id="chat-message" class="form-control" maxlength="500" autocomplete="off" placeholder="Ej. terrenos en Tehuacán…" required>
            <button type="submit" class="btn btn-horizon" aria-label="Enviar pregunta">↑</button>
        </form>
        <div class="chat-fallback" data-chat-links>
            <a href="{{ route('properties.index') }}">Explorar catálogo →</a>
        </div>
    </dialog>
</div>
