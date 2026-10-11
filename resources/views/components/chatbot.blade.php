<div class="horizon-chat" data-chat-demo>
    <button class="chat-launcher" type="button" aria-label="Abrir asistente de demostración" aria-haspopup="dialog" aria-controls="horizon-chat-dialog" hidden>
        <svg width="23" height="23" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 11.5a8 8 0 0 1-8 8H5l-3 2v-10a9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.6"/><path d="M7 10h8M7 14h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        <span>¿Te orientamos?</span>
    </button>
    <dialog id="horizon-chat-dialog" class="chat-dialog" aria-labelledby="chat-title" aria-describedby="chat-disclaimer">
        <header class="chat-header">
            <span class="chat-avatar" aria-hidden="true">H</span>
            <div><h2 id="chat-title">Asistente Horizonte</h2><span>Una idea de nuestra futura atención</span></div>
            <button class="chat-close" type="button" aria-label="Cerrar asistente" autofocus>×</button>
        </header>
        <p class="chat-disclaimer" id="chat-disclaimer">Demo visual · Respuestas predefinidas. No es IA ni atención en vivo. No envía ni guarda mensajes.</p>
        <div class="chat-messages" role="log" aria-label="Conversación de demostración" aria-live="polite" aria-relevant="additions" tabindex="0">
            <div class="chat-bubble chat-bot"><span class="chat-speaker">Horizonte · Demo</span>¡Hola! Imagina encontrar aquí ayuda para explorar tu próximo terreno. ¿Qué te gustaría conocer?</div>
        </div>
        <div class="chat-suggestions" aria-label="Preguntas de ejemplo">
            <button type="button" data-chat-topic="terrenos">Explorar terrenos</button>
            <button type="button" data-chat-topic="documentos">Ver documentación</button>
            <button type="button" data-chat-topic="visita">Coordinar una visita</button>
        </div>
        <form class="chat-form">
            <label class="visually-hidden" for="chat-message">Mensaje de prueba</label>
            <input id="chat-message" class="form-control" maxlength="500" autocomplete="off" placeholder="Escribe un mensaje de prueba…" required>
            <button type="submit" class="btn btn-horizon" aria-label="Probar mensaje">↑</button>
        </form>
        <p class="chat-footnote">No introduzcas información personal. Se borra al cerrar.</p>
        <div hidden data-chat-links>
            <a href="{{ route('properties.index') }}">Explorar catálogo de demostración →</a>
            <a href="{{ route('contact') }}">Abrir Contacto demostrativo →</a>
        </div>
    </dialog>
</div>
