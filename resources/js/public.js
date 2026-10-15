import 'bootstrap/js/dist/collapse';

document.querySelectorAll('[data-gallery]').forEach((gallery) => {
    const main = gallery.querySelector('.gallery-main');
    const buttons = gallery.querySelectorAll('[data-gallery-src]');
    buttons.forEach((button) => button.addEventListener('click', () => {
        main.src = button.dataset.gallerySrc;
        main.alt = button.dataset.galleryAlt;
        buttons.forEach((item) => item.setAttribute('aria-pressed', String(item === button)));
    }));
});

const editor = document.querySelector('#property-demo-editor');
if (editor) {
    const preview = document.querySelector('#editor-preview');
    const photo = document.querySelector('#preview-image');
    let photoUrl;
    const clearPhoto = () => {
        if (photoUrl) URL.revokeObjectURL(photoUrl);
        photoUrl = null;
        photo.removeAttribute('src');
        photo.hidden = true;
    };
    editor.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!editor.reportValidity()) return;
        const data = new FormData(editor);
        const value = (key) => String(data.get(key) || '').trim();
        const money = (number) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(number);
        document.querySelector('#preview-heading').textContent = value('title');
        document.querySelector('#preview-location').textContent = [value('zone'), value('municipality'), value('state')].filter(Boolean).join(', ');
        document.querySelector('#preview-price').textContent = money(Number(value('price'))) + ' MXN · ' + value('area') + ' m² · ' + money(Number(value('price')) / Number(value('area'))) + ' por m²';
        const details = document.querySelector('#preview-details');
        details.replaceChildren();
        const labels = { code: 'Clave', slug: 'Slug', description: 'Descripción', frontage: 'Frente (m)', depth: 'Fondo (m)', address: 'Referencia', latitude: 'Latitud', longitude: 'Longitud', services: 'Servicios', access: 'Acceso', topography: 'Topografía', land_use: 'Uso de suelo', documents: 'Resumen documental', payment_terms: 'Condiciones de pago', publication: 'Publicación simulada', availability: 'Disponibilidad', document_type: 'Tipo de documento', document_issuer: 'Emisor', document_date: 'Fecha del documento' };
        const addDetail = (label, text) => {
            const row = document.createElement('div');
            const term = document.createElement('dt');
            const definition = document.createElement('dd');
            term.textContent = label;
            definition.textContent = text || 'Por confirmar';
            row.append(term, definition);
            details.append(row);
        };
        Object.entries(labels).forEach(([key, label]) => addDetail(label, value(key)));
        addDetail('Destacado', data.has('featured') ? 'Sí' : 'No');
        const images = document.querySelector('#editor-images').files;
        addDetail('Fotografías seleccionadas', images.length + ' · primera imagen como portada');
        addDetail('Documento seleccionado (sin validar)', document.querySelector('#editor-document').files[0]?.name || 'Ninguno');
        clearPhoto();
        const first = images[0];
        if (first && ['image/jpeg', 'image/png', 'image/webp'].includes(first.type)) {
            photoUrl = URL.createObjectURL(first);
            photo.src = photoUrl;
            photo.alt = value('alt') || 'Portada seleccionada para la vista previa';
            photo.hidden = false;
            photo.onerror = () => { clearPhoto(); addDetail('Portada', 'No se pudo previsualizar este archivo.'); };
        }
        preview.hidden = false;
        preview.focus();
        preview.scrollIntoView({ behavior: 'instant', block: 'start' });
    });
    editor.addEventListener('reset', () => { preview.hidden = true; clearPhoto(); });
    editor.querySelector('[data-demo-fields]').disabled = false;
}

const chat = document.querySelector('[data-chat]');
if (chat) {
    const dialog = chat.querySelector('dialog');
    const launcher = chat.querySelector('.chat-launcher');
    const messages = chat.querySelector('.chat-messages');
    const welcome = messages.firstElementChild.cloneNode(true);
    const input = chat.querySelector('#chat-message');
    const status = chat.querySelector('.chat-status');
    const controls = [...chat.querySelectorAll('form button, [data-chat-topic]')];
    let history = [];
    let pending = null;
    const bubble = (text, author) => {
        const item = document.createElement('div');
        item.className = 'chat-bubble ' + (author === 'Tú' ? 'chat-user' : 'chat-bot');
        const speaker = document.createElement('span');
        speaker.className = 'chat-speaker';
        speaker.textContent = author;
        item.append(speaker, document.createTextNode(text));
        messages.append(item);
        while (messages.children.length > 21) messages.children[1].remove();
        messages.scrollTop = messages.scrollHeight;
        return item;
    };
    const send = async (text) => {
        if (pending || !text) return;
        const controller = new AbortController();
        pending = controller;
        controls.forEach(button => button.disabled = true);
        input.disabled = true;
        bubble(text, 'Tú');
        input.value = '';
        status.textContent = 'Consultando terrenos…';
        messages.setAttribute('aria-busy', 'true');
        const timeout = setTimeout(() => controller.abort(), 35000);
        try {
            const response = await fetch(chat.dataset.endpoint, {
                method: 'POST', signal: controller.signal, credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': chat.dataset.token},
                body: JSON.stringify({message: text, history, property_slug: chat.dataset.property || null}),
            });
            const data = await response.json();
            if (pending !== controller) return;
            if (!response.ok) {
                const fallback = response.status === 429 ? 'Límite de consultas alcanzado. Intenta más tarde o utiliza el catálogo.' : response.status === 419 ? 'Tu sesión venció. Recarga la página para continuar.' : 'No pude realizar la consulta. Intenta más tarde o utiliza el catálogo.';
                bubble(response.status === 503 ? data.message || fallback : fallback, 'Horizonte');
                return;
            }
            const item = bubble(data.message, 'Horizonte');
            for (const property of data.properties || []) {
                const card = document.createElement('div');
                card.className = 'chat-property';
                const link = document.createElement('a');
                const url = new URL(property.url, location.origin);
                if (url.origin !== location.origin || !url.pathname.startsWith('/terrenos/')) continue;
                link.href = url.href;
                link.textContent = property.title;
                card.append(link);
                for (const text of [property.location, property.summary, property.details].filter(Boolean)) {
                    const paragraph = document.createElement('p');
                    paragraph.textContent = text;
                    card.append(paragraph);
                }
                item.append(card);
            }
            history = [...history, text].slice(-6);
            messages.scrollTop = messages.scrollHeight;
        } catch (error) {
            if (pending === controller) bubble('No pude conectar con el asistente. Puedes intentar otra vez o explorar el catálogo.', 'Horizonte');
        } finally {
            clearTimeout(timeout);
            if (pending === controller) {
                pending = null;
                controls.forEach(button => button.disabled = false);
                input.disabled = false;
                messages.setAttribute('aria-busy', 'false');
                status.textContent = '';
                if (dialog.open) input.focus();
            }
        }
    };
    launcher.hidden = false;
    launcher.setAttribute('aria-expanded', 'false');

    // ── Minimize vs Full-close ─────────────────────────────────────
    // isMinimizing = true  → oculta el dialog SIN resetear nada
    // isMinimizing = false → cierre real (botón ×): resetea todo
    let isMinimizing = false;
    let savedDraft = '';   // guarda lo que el usuario estaba escribiendo

    let restoreFocus = true;
    const animatedClose = (minimize, returnFocus = true) => {
        if (!dialog.open) return;
        isMinimizing = minimize;
        restoreFocus = returnFocus;
        dialog.close();
    };

    // Abrir / reabrir
    launcher.addEventListener('click', () => {
        // Si hay borrador guardado lo restauramos al reabrir tras minimizar
        if (savedDraft) {
            input.value = savedDraft;
            savedDraft = '';
        }
        if (dialog.open) { animatedClose(true); return; }
        dialog.show();
        launcher.setAttribute('aria-expanded', 'true');
        input.focus({preventScroll: true});
    });

    // Botón × → cierre completo con reset
    chat.querySelector('.chat-close').addEventListener('click', () => animatedClose(false));

    // Non-modal dialogs do not receive clicks outside their bounds.
    document.addEventListener('pointerdown', (event) => {
        if (dialog.open && !dialog.contains(event.target) && !launcher.contains(event.target)) {
            animatedClose(true, false);
        }
    });
    document.addEventListener('keydown', (event) => {
        if (dialog.open && event.key === 'Escape') {
            event.preventDefault();
            animatedClose(true);
        }
    });

    // Manejador del evento 'close' nativo del <dialog>
    dialog.addEventListener('close', () => {
        launcher.setAttribute('aria-expanded', 'false');
        if (isMinimizing) {
            // ── Minimizado: conservar estado ──────────────────────
            // Guardamos el borrador que el usuario estaba escribiendo
            savedDraft = input.value;
            // Devolver foco al launcher sin ningún otro reset
            if (restoreFocus) launcher.focus({preventScroll: true});
            return;
        }
        // ── Cierre real: reset completo ───────────────────────────
        pending?.abort();
        pending = null;
        history = [];
        savedDraft = '';
        controls.forEach(button => button.disabled = false);
        input.disabled = false;
        messages.setAttribute('aria-busy', 'false');
        messages.replaceChildren(welcome.cloneNode(true));
        input.value = '';
        status.textContent = '';
        launcher.focus();
    });

    const questions = {terrenos: 'Muéstrame terrenos publicados', documentos: '¿Qué documentación tiene este terreno?', visita: '¿Cómo coordino una visita?'};
    chat.querySelectorAll('[data-chat-topic]').forEach(button => button.addEventListener('click', () => send(questions[button.dataset.chatTopic])));
    chat.querySelector('form').addEventListener('submit', event => {
        event.preventDefault();
        if (input.reportValidity()) send(input.value.trim());
    });
}

// Enhance the inline fallback into one accessible modal for every visit action.
const visitSection = document.querySelector('#solicitar-visita');
if (visitSection) {
    const reopen = visitSection.open || location.hash === '#solicitar-visita';
    const trigger = visitSection.querySelector('summary');
    const content = visitSection.querySelector('[data-visit-content]');
    const modal = document.createElement('dialog');
    modal.className = 'visit-dialog';
    modal.id = 'visit-dialog';
    modal.setAttribute('aria-labelledby', 'visit-dialog-title');
    const header = document.createElement('header');
    header.className = 'visit-dialog-header';
    const title = document.createElement('h2');
    title.id = 'visit-dialog-title';
    title.className = 'h4 mb-0';
    title.textContent = 'Visita este terreno';
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'visit-dialog-close';
    close.setAttribute('aria-label', 'Cerrar solicitud de visita');
    close.textContent = '×';
    header.append(title, close);
    modal.append(header, content);
    document.body.append(modal);
    visitSection.open = false;
    trigger.setAttribute('aria-haspopup', 'dialog');
    trigger.setAttribute('aria-controls', modal.id);
    let opener = trigger;
    const openVisit = (event) => {
        event?.preventDefault();
        opener = event?.currentTarget || trigger;
        if (!modal.open) modal.showModal();
        (modal.querySelector('.is-invalid') || modal.querySelector('#visitor-name') || close).focus({preventScroll: true});
    };
    trigger.addEventListener('click', openVisit);
    document.querySelectorAll('a[href="#solicitar-visita"]').forEach(link => link.addEventListener('click', openVisit));
    close.addEventListener('click', () => modal.close());
    modal.addEventListener('close', () => opener.focus({preventScroll: true}));
    if (reopen) openVisit();
}
document.querySelectorAll('[data-visit-form]').forEach(form => form.addEventListener('submit', () => {
    const button = form.querySelector('button[type=submit]');
    button.disabled = true;
    button.textContent = 'Enviando…';
}));
window.addEventListener('pageshow', () => document.querySelectorAll('[data-visit-form] button[type=submit]').forEach(button => {
    button.disabled = false;
    button.textContent = 'Solicitar visita';
}));
