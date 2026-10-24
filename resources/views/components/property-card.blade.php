@props(['property'])
<article class="property-card h-100">
    <div class="card-photo"><a href="{{ route('properties.show', $property['slug']) }}" tabindex="-1" aria-hidden="true">
        <img src="{{ asset($property['images'][0]['src']) }}" alt="" loading="lazy" width="640" height="440">
    </a><span class="status status-{{ $property['availability'] }}">{{ ($property['is_lotification'] ?? false) ? 'Lotificación' : ucfirst($property['availability']) }}</span></div>
    <div class="p-4">
        <p class="eyebrow muted mb-2">{{ $property['zone'] }} · {{ $property['state'] }}</p>
        <h3 class="h4"><a href="{{ route('properties.show', $property['slug']) }}">{{ $property['title'] }}</a></h3>
        <div class="d-flex justify-content-between gap-2 mt-4 align-items-end">
            <p class="price mb-0">{{ ($property['is_lotification'] ?? false) ? 'Desde ' : '' }}{{ $property['price'] !== null ? '$'.number_format($property['price']) : 'Sin lotes disponibles' }} <small>MXN</small></p>
            <span class="text-nowrap">@if($property['area'] !== null){{ ($property['is_lotification'] ?? false) ? 'Desde ' : '' }}{{ number_format($property['area']) }} m²@endif</span>
        </div>
        <div class="border-top pt-3 mt-3"><a class="text-link" href="{{ route('properties.show', $property['slug']) }}" aria-label="Ver detalles de {{ $property['title'] }}">{{ ($property['is_lotification'] ?? false) ? 'Ver lotes →' : 'Ver terreno →' }}</a></div>
    </div>
</article>
