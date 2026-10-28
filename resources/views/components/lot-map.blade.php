@props(['columns', 'inventory', 'slug', 'propertyId'])
@php($plan = str_starts_with($slug, 'villa-de-oshun') ? 'villa-de-oshun' : 'santa-martha-ii')
@php($savedMap = 'lot-maps/'.$propertyId.'.json')
@php($geometry = \Illuminate\Support\Facades\Storage::disk('local')->exists($savedMap) ? json_decode(\Illuminate\Support\Facades\Storage::disk('local')->get($savedMap), true) : json_decode(file_get_contents(resource_path('data/'.$plan.'.json')), true))
<section class="lot-map-section" aria-labelledby="lot-map-title" data-lot-map>
    <div class="lot-map-heading">
        <div><p class="eyebrow mb-2">Distribución de lotes</p><h2 id="lot-map-title">Ubica tu lote</h2>
            <p class="muted mb-0">Selecciona manzana y lote para consultar sus datos. El dibujo muestra únicamente las divisiones de los terrenos.</p></div>
    </div>
    <div class="row g-4 align-items-start">
        <div class="col-xl-8">
            <div class="d-flex gap-2 mb-2 flex-wrap" aria-label="Controles del plano">
                <button type="button" class="btn btn-outline-horizon" data-plan-zoom="-1" aria-label="Reducir plano">−</button>
                <button type="button" class="btn btn-outline-horizon" data-plan-zoom="1" aria-label="Ampliar plano">+</button>
                <button type="button" class="btn btn-outline-horizon" data-plan-zoom="0">Ajustar</button>

            </div>
            <div class="lot-map-viewport" role="region" aria-label="Distribución de lotes; desplázate para recorrerla" tabindex="0">
                <svg data-plan-image viewBox="{{ $geometry['viewBox'] }}" style="width:100%;max-width:none;display:block" aria-label="Distribución de las divisiones y números de lote" role="group">
                    @foreach($geometry['lots'] as $shape)
                        @php($mappedLot = $inventory->first(fn($lot) => (string)$lot->block === (string)$shape['block'] && (string)$lot->lot_number === (string)$shape['number']))
                        @php($points = collect($shape['points'])->map(fn($point) => implode(',', $point))->implode(' '))
                        <g @if($mappedLot) role="button" tabindex="0" data-shape-key="{{ $shape['block'] }}:{{ $shape['number'] }}" aria-label="Manzana {{ $shape['block'] }}, lote {{ $shape['number'] }}, {{ $mappedLot->availability_status->value }}" @endif>
                        <polygon points="{{ $points }}" fill="{{ !$mappedLot ? '#eeeeee' : ($mappedLot->availability_status->value === 'disponible' ? '#d9ecda' : ($mappedLot->availability_status->value === 'apartado' ? '#f4e6c6' : '#dddddd')) }}" stroke="#245440" stroke-width="2" />
                        <text x="{{ collect($shape['points'])->avg(fn($p) => $p[0]) }}" y="{{ collect($shape['points'])->avg(fn($p) => $p[1]) }}" text-anchor="middle" dominant-baseline="middle" font-size="14" fill="#173e35">M{{ $shape['block'] }} · {{ $shape['number'] }}</text>
                        </g>
                    @endforeach
                </svg>
                <div hidden class="lot-map-board" style="--map-columns: {{ count($columns) }}">
                    @foreach($columns as $column)
                        @if(isset($column['road']))
                            <div class="lot-map-road" aria-label="{{ $column['road'] }}"><span>{{ $column['road'] }}</span></div>
                        @else
                            <div class="lot-map-lane" role="group" aria-label="{{ $column['label'] }}">
                                <h3>{{ $column['label'] }}</h3>
                                @foreach($column['lots'] as $slot)
                                    @if($slot['lot'])
                                        @php($lot = $slot['lot'])
                                        <button type="button" class="lot-map-plot is-{{ $lot->availability_status->value }}" data-map-url="{{ route('properties.show', $lot->slug) }}"
                                           data-map-lot="{{ $lot->block }}:{{ $lot->lot_number }}"
                                           data-map-title="Manzana {{ $lot->block }} · Lote {{ $lot->lot_number }}"
                                           data-map-price="{{ '$'.number_format($lot->price_mxn, 2) }} MXN"
                                           data-map-area="{{ number_format($lot->area_m2, 2) }} m²"
                                           data-map-status="{{ ucfirst($lot->availability_status->value) }}"
                                           aria-label="Manzana {{ $lot->block }}, lote {{ $lot->lot_number }}, {{ $lot->availability_status->value }}. {{ number_format($lot->area_m2, 2) }} metros cuadrados. {{ '$'.number_format($lot->price_mxn, 2) }} pesos. Seleccionar lote" aria-pressed="false">
                                            <span aria-hidden="true">{{ $lot->lot_number }}</span>
                                            <small aria-hidden="true">{{ ucfirst($lot->availability_status->value) }}</small>
                                        </button>
                                    @else
                                        <span class="lot-map-plot is-unlisted" aria-label="Manzana {{ $column['block'] }}, lote {{ $slot['number'] }} sin publicación"><span aria-hidden="true">{{ $slot['number'] }}</span></span>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
            <p class="small muted mt-2 mb-0">Usa los controles para ampliar y las barras de desplazamiento para recorrer la distribución.</p>
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
                <a class="btn btn-horizon mt-3 w-100" data-map-link hidden>Ver ficha del lote →</a>
            </div>
        </div>
    </div>
</section>
