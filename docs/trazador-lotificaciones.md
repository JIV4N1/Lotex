# Trazador de lotificaciones

Acceso: /admin/trazador o /superadmin/trazador, con la sesión del panel correspondiente.

1. Seleccionar una lotificación existente con sus lotes dados de alta.
2. Cargar una guía JPG, PNG o WebP (hasta 20 MB y 10000 píxeles por lado). La guía se carga únicamente en el navegador y no se sube al servidor.
3. Seleccionar un lote, pulsar cada esquina en orden y cerrar el lote. Deshacer punto corrige el último vértice; para reemplazar un polígono se vuelve a trazar el lote seleccionado.
4. Revisar Vista limpia y publicar el trazado revisado. Las coordenadas se guardan en el disco local privado, en lot-maps/{id}.json. Este disco debe contar con el montaje persistente de producción.
5. Al regresar, el mapa se recupera. Para continuar trazando se selecciona de nuevo la misma imagen guía con idénticas dimensiones.

Los nombres, superficies, frente y fondo proceden de los registros existentes; no se calculan medidas físicas a partir de píxeles. No hay reconocimiento automático ni OCR. La validación actual limita estructura, número de vértices, coordenadas y pertenencia de cada lote a la lotificación; la revisión de cruces y coincidencia con el documento corresponde al administrador. Publicar reemplaza el mapa guardado; no existe historial de versiones en esta primera versión.

## Propuestas con Gemini

En el editor, el recuadro azul delimita el recorte definido por X/Y/Ancho/Alto. Al pulsar Detectar lotes con Gemini se envía ese recorte a Google, usando GEMINI_API_KEY. GEMINI_TRACING_MODEL permite escoger el modelo del trazador independientemente del chatbot; el valor predeterminado es gemini-2.5-flash. La imagen no se persiste en el disco público. El análisis admite tres solicitudes por usuario cada diez minutos; el consumo se registra con la etiqueta lot_tracing en el log de Laravel, sin imagen ni credenciales.

Los contornos naranjas son propuestas. Incorporar propuesta añade solo lotes que aún no tienen trazado; descartar no modifica el mapa existente. Revisar cada contorno antes de publicar. Esta primera integración utiliza visión de Gemini; no incluye todavía un detector geométrico independiente, validación de cruces entre polígonos ni lectura de medidas por OCR. Las medidas siguen procediendo de los registros existentes. La exactitud no está garantizada.
