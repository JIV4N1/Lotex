@props(['property'])
<section class="detail-section" id="medidas">
    <p class="eyebrow muted">El terreno, de un vistazo</p>
    <h2>Medidas y distribución</h2>
    <div class="plot-plan my-4" role="img" aria-label="Esquema ilustrativo rectangular: frente {{ $property['frontage'] }} metros, fondo {{ $property['depth'] }} metros. No es un plano topográfico.">
        <span class="plot-depth">Fondo · {{ $property['depth'] }} m</span>
        <div class="plot-outline"><span>{{ number_format($property['area']) }} m²<small>Superficie de ejemplo</small></span></div>
        <span class="plot-front">Frente · {{ $property['frontage'] }} m</span>
        <span class="plot-road">ACCESO ILUSTRATIVO</span>
    </div>
    <p class="small muted">Esquema sin escala. La forma, las colindancias y las medidas deberán confirmarse con un levantamiento real.</p>
</section>
<section class="detail-section" id="ubicacion">
    <p class="eyebrow muted">Conoce el entorno</p>
    <h2>Ubicación del terreno</h2>
    <div class="location-placeholder my-4">
        <span class="location-pin" aria-hidden="true">⌖</span>
        <strong>{{ $property['zone'] }}, Puebla</strong>
        <span>Zona de demostración · Punto exacto pendiente</span>
    </div>
    <p class="muted">Aquí podrás abrir la ubicación exacta en Google Maps cuando se incorporen coordenadas verificadas.</p>
    <button class="btn btn-outline-horizon" disabled>Ver ubicación exacta · Próximamente</button>
    <a class="text-link d-block mt-3" href="{{ route('contact', ['terreno' => $property['slug']]) }}">Consultar la ubicación de este terreno →</a>
</section>
<section class="detail-section" id="documentacion">
    <p class="eyebrow muted">Información para decidir</p>
    <h2>Documentación del terreno</h2>
    <p class="muted">Un espacio para consultar los documentos autorizados de esta propiedad. Todavía no hay archivos reales ni validación jurídica.</p>
    <div class="document-preview">
        <span class="document-icon" aria-hidden="true">PDF</span>
        <div><h3 class="h6">Documento acreditativo</h3><p class="small muted mb-0">Tipo, emisor y fecha por confirmar.<br>Se publicará una versión con datos personales protegidos.</p></div>
        <span class="pending-label">Pendiente de revisión</span>
    </div>
    <p class="small muted mt-3 mb-0">La ficha no acredita propiedad ni autorización de venta. La revisión de documentos deberá realizarse antes de una operación.</p>
</section>
