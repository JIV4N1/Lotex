@props(['property', 'floating' => false])
@php
    $group = $property['is_lotification'] ?? false;
    $acceptsVisits = $group
        ? ($property['development_status'] ?? 'consultas') === 'consultas'
        : $property['availability'] !== 'vendido';
    $current = request()->routeIs('properties.show') && request()->route('slug') === $property['slug'];
    $target = $current ? '#solicitar-visita' : route('properties.show', $property['slug']).'#solicitar-visita';
@endphp
@if($acceptsVisits)
    <div class="{{ $floating ? 'mobile-inquiry d-lg-none' : 'mt-3 mb-3' }}">
        <a class="btn btn-horizon {{ $floating ? 'w-100' : '' }}" href="{{ $target }}">{{ $group ? 'Quiero conocer esta lotificación' : 'Quiero visitar este terreno' }}</a>
    </div>
@endif
