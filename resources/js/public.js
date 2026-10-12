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

const chat = document.querySelector('[data-chat-demo]');
if (chat) {
    const dialog = chat.querySelector('dialog');
    const launcher = chat.querySelector('.chat-launcher');
    const messages = chat.querySelector('.chat-messages');
    const welcome = messages.firstElementChild.cloneNode(true);
    const input = chat.querySelector('#chat-message');
    const links = chat.querySelectorAll('[data-chat-links] a');
    const replies = {
        terrenos: ['Puedes probar los filtros por zona, precio y superficie en nuestro catálogo. Las fichas muestran la información publicada por la administración.', 0],
        documentos: ['La ficha incluye un espacio para documentación revisada. Consulta los archivos disponibles en cada terreno; este asistente no verifica su validez.', null],
        visita: ['En el futuro podremos orientarte para solicitar una visita. Por ahora, Contacto es una maqueta y no envía solicitudes ni confirma citas.', 1],
        general: ['Este es un ejemplo visual con respuestas predefinidas. Todavía no puedo interpretar preguntas. Prueba una opción o explora el catálogo.', 0],
    };
    const bubble = (text, author, linkIndex = null) => {
        const item = document.createElement('div');
        item.className = 'chat-bubble ' + (author === 'Tú' ? 'chat-user' : 'chat-bot');
        const speaker = document.createElement('span');
        speaker.className = 'chat-speaker';
        speaker.textContent = author;
        item.append(speaker, document.createTextNode(text));
        if (linkIndex !== null) item.append(links[linkIndex].cloneNode(true));
        messages.append(item);
        while (messages.children.length > 21) messages.children[1].remove();
        messages.scrollTop = messages.scrollHeight;
    };
    launcher.hidden = false;
    launcher.addEventListener('click', () => dialog.showModal());
    chat.querySelector('.chat-close').addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => {
        messages.replaceChildren(welcome.cloneNode(true));
        input.value = '';
        launcher.focus();
    });
    chat.querySelectorAll('[data-chat-topic]').forEach((button) => {
        button.addEventListener('click', () => {
            bubble(button.textContent, 'Tú');
            const [text, link] = replies[button.dataset.chatTopic];
            bubble(text, 'Horizonte · Demo', link);
        });
    });
    chat.querySelector('form').addEventListener('submit', (event) => {
        event.preventDefault();
        const text = input.value.trim();
        if (!text) return;
        bubble(text, 'Tú');
        bubble(replies.general[0], 'Horizonte · Demo', replies.general[1]);
        input.value = '';
        input.focus();
    });
}
