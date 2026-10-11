@props(['property'])
<article class="property-card h-100">
    <div class="card-photo"><a href="{{ route('properties.show', $property['slug']) }}" tabindex="-1" aria-hidden="true">
        <img src="{{ asset($property['images'][0]['src']) }}" alt="" loading="lazy" width="640" height="440">
    </a><span class="status status-{{ $property['availability'] }}">{{ ucfirst($property['availability']) }}</span></div>
    <div class="p-4">
        <p class="eyebrow muted mb-2">{{ $property['zone'] }} · Puebla</p>
        <h3 class="h4"><a href="{{ route('properties.show', $property['slug']) }}">{{ $property['title'] }}</a></h3>
        <div class="d-flex justify-content-between gap-2 mt-4 align-items-end">
            <p class="price mb-0">${{ number_format($property['price']) }} <small>MXN</small></p>
            <span class="text-nowrap">{{ number_format($property['area']) }} m²</span>
        </div>
        <p class="small muted border-top pt-3 mt-3 mb-0">Ficha ficticia · Fotografía ilustrativa</p>
    </div>
</article>
