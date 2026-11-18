@extends('layouts.public')
@section('title', $property['title'])
@section('content')
<div class="container section-space">
<nav aria-label="Ruta de navegación"><a href="{{ route('properties.index', session('catalog_filters',[])) }}">← Volver al catálogo</a></nav>
<p class="eyebrow mt-4">Lotificación · {{ $property['zone'] }}, {{ $property['municipality'] }}</p>
<h1>{{ $property['title'] }}</h1>
<p>{{ $property['description'] }}</p>
@if($property['development_status'] === 'consultas')<p><a class="btn btn-horizon" href="#solicitar-visita">Quiero conocer esta lotificación</a></p>@endif
<div class="gallery" data-gallery><img id="gallery-main" class="gallery-main" src="{{ $property['images'][0]['src'] }}" alt="{{ $property['images'][0]['alt'] }}" width="1200" height="800"><div class="thumbnails mt-3">@foreach($property['images'] as $image)<button type="button" class="thumbnail" data-gallery-src="{{ $image['src'] }}" data-gallery-alt="{{ $image['alt'] }}" aria-label="Ver imagen {{ $loop->iteration }}" aria-pressed="{{ $loop->first ? 'true':'false' }}"><img src="{{ $image['src'] }}" alt="" width="180" height="120"></button>@endforeach</div></div>
<section class="detail-section"><h2>Información de la lotificación</h2><p>{{ $property['development_status_label'] }} @if($property['price'] !== null) · Desde {{ '$'.number_format($property['price'],2) }} MXN @endif</p>
<dl class="spec-grid">
@if($property['area'] !== null)<div><dt>Superficies desde</dt><dd>{{ number_format($property['area'],2) }} m² @if($property['area_max'] > $property['area']) hasta {{ number_format($property['area_max'],2) }} m² @endif</dd></div>@endif
@if($property['down_payment'] !== null)<div><dt>Enganche desde</dt><dd>${{ number_format($property['down_payment'],2) }} MXN</dd></div>@endif
@if($property['monthly_payment'] !== null)<div><dt>Mensualidades desde</dt><dd>${{ number_format($property['monthly_payment'],2) }} MXN</dd></div>@endif
@if($property['weekly_payment'] !== null)<div><dt>Pagos semanales desde</dt><dd>${{ number_format($property['weekly_payment'],2) }} MXN</dd></div>@endif
</dl><p class="small muted">Consulta disponibilidad, medidas y condiciones con un asesor.</p>
<p>Servicios: {{ implode(', ', $property['services']) ?: 'Por confirmar' }}</p>
<dl class="detail-list">@foreach(['access'=>'Acceso','topography'=>'Topografía','land_use'=>'Uso de suelo','payment_terms'=>'Condiciones generales de pago'] as $key=>$label)<div><dt>{{ $label }}</dt><dd>{{ $property[$key] }}</dd></div>@endforeach</dl>
@if($property['latitude'] !== null && $property['longitude'] !== null)<a href="https://www.google.com/maps/search/?api=1&query={{ $property['latitude'] }},{{ $property['longitude'] }}" target="_blank" rel="noopener noreferrer">Ver ubicación en Google Maps ↗</a>@endif
<h3 class="h5 mt-4">Plano y documentación</h3><p>{{ $property['documents'] }}</p>@forelse($property['document_files'] as $document)<a class="d-block mb-2" href="{{ $document['url'] }}">{{ $document['title'] }} · Descargar PDF</a>@empty<p class="muted">Documentos públicos pendientes de incorporar.</p>@endforelse
<p class="small muted">Consulta las condiciones particulares de cada lote. Publicar un documento no certifica su validez jurídica.</p></section>
<section class="detail-section">
<h2>Plano general</h2>
<x-visit-link :property="$property" />
@if($property['site_plan'])
<a href="{{ $property['site_plan']['src'] }}" target="_blank" rel="noopener noreferrer" aria-label="Abrir plano ampliado">
<img class="lotification-reference-plan img-fluid rounded" src="{{ $property['site_plan']['src'] }}" alt="{{ $property['site_plan']['alt'] ?: 'Plano de '.$property['title'] }}" loading="lazy"></a>
<p class="small muted mt-2">Abre el plano para ampliarlo. Consulta disponibilidad y medidas con un asesor.</p>
@else
<p>Un asesor te orientará sobre la distribución al coordinar tu visita.</p>
@endif
</section>
<section class="detail-section"><h2>Conoce el lugar en persona</h2><x-visit-request-form :property="$property" /></section>
</div>
<x-visit-link :property="$property" floating />
@endsection
