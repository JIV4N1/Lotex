@props(['columns', 'inventory', 'slug', 'propertyId'])
@php($savedMap = 'lot-maps/'.$propertyId.'.json')
@php($geometry = \Illuminate\Support\Facades\Storage::disk('local')->exists($savedMap) ? json_decode(\Illuminate\Support\Facades\Storage::disk('local')->get($savedMap), true) : null)
@php($geometry = is_array($geometry) && ($geometry['reviewed'] ?? false) === true ? $geometry : null)
@if(!$geometry && $slug === 'santa-martha-ii')
    @php($geometry = json_decode(file_get_contents(resource_path('data/santa-martha-ii-guide.json')), true))
    @php($geometry['lots'] = collect($geometry['lots'])->map(function($shape) use ($inventory) { $lot = $inventory->first(fn($lot) => (string)$lot->block === (string)$shape['block'] && (string)$lot->lot_number === (string)$shape['number']); $shape['id'] = $lot?->id; return $shape; })->all())
@endif
<section class="lot-map-section" aria-labelledby="lot-map-title" data-lot-map>
    <div class="lot-map-heading">
        <div><p class="eyebrow mb-2">Distribución de lotes</p><h2 id="lot-map-title">Ubica tu lote</h2>
            <p class="muted mb-0">Elige un lote en la distribución o búscalo por manzana y número. Consulta su precio, medidas y disponibilidad actualizados.</p></div>
    </div>
    <div class="row g-4 align-items-start">
        <div class="col-xl-8">
            @if($geometry)
            <div class="d-flex gap-2 mb-2 flex-wrap" aria-label="Controles del plano">
                <button type="button" class="btn btn-outline-horizon" data-plan-zoom="-1" aria-label="Reducir plano">−</button>
                <button type="button" class="btn btn-outline-horizon" data-plan-zoom="1" aria-label="Ampliar plano">+</button>
                <button type="button" class="btn btn-outline-horizon" data-plan-zoom="0">Ajustar</button>

            </div>
            <div class="lot-map-viewport" role="region" aria-label="Distribución de lotes; desplázate para recorrerla" tabindex="0">
                @if($geometry['schematic'] ?? false)<p class="small muted">Guía de ubicación por manzana, sin escala. El tamaño de las figuras no representa las medidas del terreno; consulta la ficha del lote.</p>@endif
                <svg data-plan-image viewBox="{{ $geometry['viewBox'] }}" style="width:100%;max-width:none;display:block" aria-label="Distribución de las divisiones y números de lote" role="group">
                    @foreach($geometry['labels'] ?? [] as $label)<text x="{{ $label['x'] }}" y="{{ $label['y'] }}" text-anchor="middle" font-size="12" fill="#526357">{{ $label['text'] }}</text>@endforeach
                    @foreach($geometry['lots'] as $shape)
                        @php($mappedLot = $inventory->firstWhere('id', $shape['id'] ?? null))
                        @php($points = collect($shape['points'])->map(fn($point) => implode(',', $point))->implode(' '))
                        <g @if($mappedLot) role="button" tabindex="0" data-shape-key="{{ $mappedLot->block }}:{{ $mappedLot->lot_number }}" aria-label="Manzana {{ $mappedLot->block }}, lote {{ $mappedLot->lot_number }}, {{ $mappedLot->availability_status->value }}" @endif>
                        <polygon points="{{ $points }}" fill="{{ !$mappedLot ? '#eeeeee' : ($mappedLot->availability_status->value === 'disponible' ? '#d9ecda' : ($mappedLot->availability_status->value === 'apartado' ? '#f4e6c6' : '#dddddd')) }}" stroke="#245440" stroke-width="2" />
                        <text x="{{ collect($shape['points'])->avg(fn($p) => $p[0]) }}" y="{{ collect($shape['points'])->avg(fn($p) => $p[1]) }}" text-anchor="middle" dominant-baseline="middle" font-size="14" fill="#173e35">{{ $mappedLot ? 'M'.$mappedLot->block.' · L'.$mappedLot->lot_number : 'Sin publicación' }}</text>
                        </g>
                    @endforeach
                </svg>
            </div>
            <p class="small muted mt-2">Verde: disponible · Arena: apartado · Gris: vendido o sin publicación.</p>
            @else
            <div class="lot-map-pending"><h3 class="h5">Encuentra tu lote por manzana</h3><p class="muted mb-0">La distribución visual está en revisión. Puedes consultar todos los lotes publicados en la lista y solicitar información sobre su ubicación.</p></div>
            @endif
            <details class="lot-map-list" @if(!$geometry) open @endif>
                <summary class="fw-semibold">Elegir por manzana y número · {{ $inventory->count() }} lotes</summary>
                @forelse($inventory->groupBy('block') as $block => $items)
                <h3 class="h6 mt-4">Manzana {{ $block }}</h3><div class="lot-map-list-grid">
                @foreach($items as $lot)
                <button type="button" class="lot-map-plot is-{{ $lot->availability_status->value }}"
                    data-map-lot="{{ $lot->block }}:{{ $lot->lot_number }}"
                    data-map-url="{{ route('properties.show', $lot->slug) }}"
                    data-map-title="Manzana {{ $lot->block }} · Lote {{ $lot->lot_number }}"
                    data-map-price="${{ number_format($lot->price_mxn, 2) }} MXN"
                    data-map-area="{{ number_format($lot->area_m2, 2) }} m²"
                    data-map-front="{{ $lot->frontage_m !== null ? number_format($lot->frontage_m, 2).' m' : 'Por confirmar' }}"
                    data-map-depth="{{ $lot->depth_m !== null ? number_format($lot->depth_m, 2).' m' : 'Por confirmar' }}"
                    data-map-status="{{ ucfirst($lot->availability_status->value) }}"
                    aria-label="Manzana {{ $lot->block }}, lote {{ $lot->lot_number }}, {{ $lot->availability_status->value }}" aria-pressed="false">
                    <span>Lote {{ $lot->lot_number }}</span><small>{{ ucfirst($lot->availability_status->value) }}</small>
                </button>
                @endforeach</div>
                @empty<p class="muted mt-3">No hay lotes publicados todavía.</p>@endforelse
            </details>
        </div>
        <div class="col-xl-4">
            <div class="lot-map-info">
                <label for="lot-map-picker" class="form-label fw-semibold">Elige manzana y lote</label>
                <select id="lot-map-picker" class="form-select" data-map-picker>
                    <option value="">Selecciona un lote</option>
                    @foreach($inventory as $lot)
                        <option value="{{ $lot->block }}:{{ $lot->lot_number }}">Manzana {{ $lot->block }} · Lote {{ $lot->lot_number }} · {{ ucfirst($lot->availability_status->value) }}</option>
                    @endforeach
                </select>
                <div class="lot-map-summary mt-4" data-map-summary aria-live="polite" aria-atomic="true">
                    <p class="muted mb-0">Selecciona manzana y lote para consultar sus datos actuales.</p>
                </div>
                <a class="btn btn-outline-horizon mt-3 w-100" data-map-link hidden>Ver detalles del lote →</a>
                <a class="btn btn-horizon mt-2 w-100" data-map-visit hidden>Quiero visitar este lote</a>
            </div>
        </div>
    </div>
</section>
