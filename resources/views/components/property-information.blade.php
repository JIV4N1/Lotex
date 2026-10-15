@props(['property'])
<section class="detail-section" id="medidas">
    <p class="eyebrow muted">El terreno, de un vistazo</p><h2>Medidas y distribución</h2>
    <dl class="spec-grid">
        <div><dt>Superficie</dt><dd>{{ number_format($property['area'], 2) }} m²</dd></div>
        <div><dt>Frente</dt><dd>{{ $property['frontage'] !== null ? $property['frontage'].' m' : 'Por confirmar' }}</dd></div>
        <div><dt>Fondo</dt><dd>{{ $property['depth'] !== null ? $property['depth'].' m' : 'Por confirmar' }}</dd></div>
    </dl>
    <p class="small muted">Las medidas no describen por sí solas la forma del terreno. Consulta los planos disponibles en Documentación o solicita más información.</p>
</section>
<section class="detail-section" id="ubicacion">
    <p class="eyebrow muted">Conoce el entorno</p><h2>Ubicación del terreno</h2>
    <div class="location-placeholder my-4"><span class="location-pin" aria-hidden="true">⌖</span><strong>{{ $property['zone'] }}, {{ $property['municipality'] }}, {{ $property['state'] }}</strong><span>{{ $property['reference_address'] ?: 'Dirección de referencia por confirmar' }}</span></div>
    @if($property['latitude'] !== null && $property['longitude'] !== null)
        <a class="btn btn-outline-horizon" href="https://www.google.com/maps/search/?api=1&amp;query={{ $property['latitude'] }},{{ $property['longitude'] }}" target="_blank" rel="noopener noreferrer">Ver ubicación exacta en Google Maps ↗</a>
    @else
        <p class="muted">Ubicación exacta pendiente de confirmar.</p>
    @endif
    @if($property['availability'] !== 'vendido')<a class="text-link d-block mt-3" href="#solicitar-visita" data-open-visit>Solicitar una visita →</a>@endif
</section>
<section class="detail-section" id="documentacion">
    <p class="eyebrow muted">Información para decidir</p><h2>Documentación y planos</h2>
    <p class="muted">{{ $property['documents'] }}</p>
    @forelse($property['document_files'] as $document)
        <div class="document-preview mb-3"><span class="document-icon" aria-hidden="true">PDF</span><div><h3 class="h6">{{ $document['title'] }}</h3><p class="small muted mb-0">{{ $document['type'] ?: 'Documento' }}@if($document['issuer']) · {{ $document['issuer'] }}@endif @if($document['issued_at']) · {{ $document['issued_at'] }}@endif</p><a href="{{ $document['url'] }}" class="text-link">Descargar PDF</a></div></div>
    @empty
        <p class="small muted">No hay documentos autorizados para consulta pública en esta ficha.</p>
    @endforelse
    <p class="small muted mt-3 mb-0">La publicación de un archivo no certifica su validez jurídica. Solicita una revisión documental antes de una operación.</p>
</section>
