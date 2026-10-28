@extends('layouts.public')
@section('title', $property['title'])
@section('content')
<div class="container section-space">
<nav aria-label="Ruta de navegación"><a href="{{ route('properties.index', session('catalog_filters',[])) }}">← Volver al catálogo</a></nav>
<p class="eyebrow mt-4">Lotificación · {{ $property['zone'] }}, {{ $property['municipality'] }}</p>
<h1>{{ $property['title'] }}</h1>
<p>{{ $property['description'] }}</p>
<div class="gallery" data-gallery><img id="gallery-main" class="gallery-main" src="{{ $property['images'][0]['src'] }}" alt="{{ $property['images'][0]['alt'] }}" width="1200" height="800"><div class="thumbnails mt-3">@foreach($property['images'] as $image)<button type="button" class="thumbnail" data-gallery-src="{{ $image['src'] }}" data-gallery-alt="{{ $image['alt'] }}" aria-label="Ver imagen {{ $loop->iteration }}" aria-pressed="{{ $loop->first ? 'true':'false' }}"><img src="{{ $image['src'] }}" alt="" width="180" height="120"></button>@endforeach</div></div>
<section class="detail-section"><h2>Información de la lotificación</h2><p>{{ $property['available_lots'] }} lotes disponibles @if($property['price'] !== null) · Desde {{ '$'.number_format($property['price'],2) }} MXN @endif</p>
<p>Servicios: {{ implode(', ', $property['services']) ?: 'Por confirmar' }}</p>
<dl class="detail-list">@foreach(['access'=>'Acceso','topography'=>'Topografía','land_use'=>'Uso de suelo','payment_terms'=>'Condiciones generales de pago'] as $key=>$label)<div><dt>{{ $label }}</dt><dd>{{ $property[$key] }}</dd></div>@endforeach</dl>
@if($property['latitude'] !== null && $property['longitude'] !== null)<a href="https://www.google.com/maps/search/?api=1&query={{ $property['latitude'] }},{{ $property['longitude'] }}" target="_blank" rel="noopener noreferrer">Ver ubicación en Google Maps ↗</a>@endif
<h3 class="h5 mt-4">Plano y documentación</h3><p>{{ $property['documents'] }}</p>@forelse($property['document_files'] as $document)<a class="d-block mb-2" href="{{ $document['url'] }}">{{ $document['title'] }} · Descargar PDF</a>@empty<p class="muted">Documentos públicos pendientes de incorporar.</p>@endforelse
<p class="small muted">Consulta las condiciones particulares de cada lote. Publicar un documento no certifica su validez jurídica.</p></section>
<section id="lotes"><h2>Elige tu lote</h2><p>Consulta sus medidas y precio; después solicita una visita con tu nombre y WhatsApp.</p>
<x-lot-map :columns="$lotMap" :inventory="$inventory" :slug="$property['slug']" :property-id="$mapPropertyId" :reference-plan="$property['site_plan'] ?? null" :title="$property['title']" />
<div class="border rounded p-3 mb-4">
<h3 class="h5">Disponibilidad general</h3>
<p class="small mb-2">Inventario completo publicado, independiente de los filtros.</p>
<p class="mb-0">@foreach(['disponible'=>'Disponibles','apartado'=>'Apartados','vendido'=>'Vendidos'] as $status=>$label)<span class="me-3">{{ $label }}: <strong>{{ $inventory->where('availability_status.value', $status)->count() }}</strong></span>@endforeach</p>
</div>
@if($errors->any())<p class="alert alert-warning" role="alert">Revisa los filtros: usa importes y superficies no negativos.</p>@endif
<form method="get" action="{{ route('properties.show',$property['slug']) }}#lotes" class="row g-3 mb-4">
<div class="col-md-3"><label for="block" class="form-label">Manzana</label><select id="block" name="block" class="form-select"><option value="">Todas</option>@foreach($blocks as $block)<option @selected(($filters['block'] ?? '')===$block)>{{ $block }}</option>@endforeach</select></div>
<div class="col-md-3"><label for="availability" class="form-label">Disponibilidad</label><select id="availability" name="availability" class="form-select"><option value="">Todas</option>@foreach(['disponible'=>'Disponible','apartado'=>'Apartado','vendido'=>'Vendido'] as $value=>$label)<option value="{{ $value }}" @selected(($filters['availability'] ?? '')===$value)>{{ $label }}</option>@endforeach</select></div>
<div class="col-md-3"><label for="max_price" class="form-label">Precio máximo (MXN)</label><input id="max_price" name="max_price" type="number" min="0" step="0.01" value="{{ $filters['max_price'] ?? '' }}" class="form-control"></div>
<div class="col-md-3"><label for="min_area" class="form-label">Superficie mínima (m²)</label><input id="min_area" name="min_area" type="number" min="0" step="0.01" value="{{ $filters['min_area'] ?? '' }}" class="form-control"></div>
<div><button class="btn btn-horizon">Filtrar lotes</button><a class="ms-3" href="{{ route('properties.show',$property['slug']) }}#lotes">Limpiar filtros</a></div>
</form>
<p role="status">{{ $lots->total() }} lotes encontrados</p><div class="row g-3">
@forelse($lots as $lot)<div class="col-md-6 col-lg-4"><article class="property-card h-100 p-4"><h3 class="h5">Manzana {{ $lot->block }} · Lote {{ $lot->lot_number }}</h3><p>{{ ucfirst($lot->availability_status->value) }}</p><p class="price">{{ '$'.number_format($lot->price_mxn,2) }} <small>MXN</small></p><p>{{ number_format($lot->area_m2,2) }} m²</p><a class="btn btn-outline-horizon" href="{{ route('properties.show',$lot->slug) }}" aria-label="Ver manzana {{ $lot->block }}, lote {{ $lot->lot_number }}">Ver lote →</a></article></div>@empty<p>No hay lotes con esos filtros.</p>@endforelse
</div><nav class="d-flex justify-content-between mt-4" aria-label="Páginas de lotes">@if($lots->previousPageUrl())<a href="{{ $lots->previousPageUrl() }}#lotes">← Anterior</a>@endif<span>Página {{ $lots->currentPage() }} de {{ $lots->lastPage() }}</span>@if($lots->nextPageUrl())<a href="{{ $lots->nextPageUrl() }}#lotes">Siguiente →</a>@endif</nav></section>
</div>
@endsection
