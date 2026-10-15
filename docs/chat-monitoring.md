# Monitor del chatbot y revisión de interfaz — 15/10/2026

Acceso: `/admin/chatbot`, exclusivamente administradores. Se registran preguntas nuevas que llegan a intentar la llamada a Gemini; no hay histórico retroactivo. Pregunta y respuesta textual están cifradas con APP_KEY. No se guarda el historial de seis preguntas, ni IP, cookies, identificadores de sesión o claves. El contexto contiene solo el slug de la ficha enviada, no acredita identidad ni una solicitud de visita.

La pantalla incluye resumen de registros retenidos, tokens reportados, errores y presupuesto diario local; lista paginada con filtros por fecha (México) y resultado; detalle de lectura con modelo, estado HTTP y duración. Las tarjetas no se filtran con la lista. No se calculan precios ni se presenta esto como factura o cuota oficial de Google. Los tokens totales se toman de `usageMetadata.totalTokenCount`, sin inferirlos de otros campos. Los fallos sin métricas quedan como «No reportados».

Retención visible configurable: `CHAT_RETENTION_DAYS`, 30 días por defecto. La eliminación física requiere ejecutar el scheduler o `php artisan model:prune --model="App\Models\ChatInteraction"`; no se instaló una tarea del sistema. No regenerar APP_KEY sin una estrategia para los datos cifrados. Consultar README para operación.

Mejoras de interfaz: foco visible en administración, controles táctiles del chatbot de 44 px, movimiento reducido aplicado también a animaciones, ausencia de robo de foco cuando termina una respuesta después de salir del chatbot, errores de Contacto asociados a cada campo, tarjetas/especificaciones y galería adaptadas a pantallas pequeñas. Se corrigió un desbordamiento horizontal de Inicio causado por gutters de Bootstrap en móvil.

Comprobaciones ejecutadas:

- Migración aditiva aplicada en desarrollo y en PostgreSQL de pruebas; sin borrado de datos existentes.
- Suite completa: **64 aprobadas, 838 assertions, cero omitidas**. Incluye persistencia cifrada, tokens de proveedor, fallos, acceso administrativo, filtros por estado y registro antiguo fuera de la ventana visible. SQL/XSS, CSRF, formularios y autorizaciones existentes también pasaron.
- `npm run build`: correcto. `git diff --check`: sin errores de espacios.
- Navegador: Inicio y Contacto en viewport móvil 390×844 sin desbordamiento horizontal (ancho útil y scroll de 375 px). Apertura del chatbot, minimización al abrir menú y cierre por Escape; Contacto también inspeccionado en escritorio 1366×900. Acceso sin sesión a `/admin/chatbot` redirige a login.
- El contenido administrativo y su acción de lectura se verificaron con pruebas HTTP/Livewire; no se inició una sesión administrativa real en el navegador. No se hicieron llamadas reales a Gemini ni se midió facturación. No es una certificación WCAG ni una auditoría de penetración completa.

Referencia de tokens: https://ai.google.dev/api/generate-content#UsageMetadata
