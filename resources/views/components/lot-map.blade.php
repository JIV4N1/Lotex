@props(['columns', 'inventory'])
<section class="lot-map-section" aria-labelledby="lot-map-title" data-lot-map>
    <div class="lot-map-heading">
        <div><p class="eyebrow mb-2">Croquis interactivo</p><h2 id="lot-map-title">Ubica tu lote</h2>
            <p class="muted mb-0">Selecciona un lote para ver su precio, superficie y disponibilidad. El croquis muestra la distribución relativa, sin escala ni linderos exactos.</p></div>
    </div>
    <div class="lot-map-key" aria-label="Disponibilidad por color y texto">
        <span><i class="lot-map-key-color is-available" aria-hidden="true"></i> Disponible</span>
        <span><i class="lot-map-key-color is-reserved" aria-hidden="true"></i> Apartado</span>
        <span><i class="lot-map-key-color is-sold" aria-hidden="true"></i> Vendido</span>
        <span><i class="lot-map-key-color is-unlisted" aria-hidden="true"></i> Sin publicación</span>
    </div>
    <div class="row g-4 align-items-start">
        <div class="col-xl-8">
            <div class="lot-map-viewport" role="region" aria-label="Croquis de lotes; desliza para recorrerlo" tabindex="0">
                <div class="lot-map-board" style="--map-columns: {{ count($columns) }}">
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
            <p class="small muted mt-2 mb-0">Recorre el croquis desplazándolo; también puedes elegir un lote en la lista. La posición es orientativa y debe confirmarse durante la visita.</p>
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
                    <p class="muted mb-0">Toca un lote en el croquis o selecciónalo arriba para ver sus datos.</p>
                </div>
                <a class="btn btn-horizon mt-3 w-100" data-map-link hidden>Ver ficha del lote →</a>
            </div>
        </div>
    </div>
</section>
