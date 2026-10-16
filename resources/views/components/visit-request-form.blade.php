@props(['property'])
@if($property['availability'] === 'vendido')
    <p class="muted">Este terreno ya fue vendido. Explora el catálogo para encontrar otras opciones.</p>
    <a class="btn btn-outline-horizon w-100" href="{{ route('properties.index') }}">Ver otros terrenos</a>
@else
    <details id="solicitar-visita" class="visit-request" @if(session('visit_requested') || $errors->any()) open @endif>
        <summary class="btn btn-horizon w-100">Quiero visitar este terreno</summary>
        <div class="pt-4" data-visit-content>
            <p class="small mb-3">Tu visita a <strong>{{ $property['title'] }}</strong></p>
            @if($property['availability'] === 'apartado')<p class="small muted">Este terreno está apartado. El equipo te confirmará si es posible visitarlo o te propondrá otras opciones.</p>@endif
            @if(session('visit_requested'))
                <div class="alert alert-success mb-0" role="status">
                    <strong>Recibimos tu solicitud.</strong>
                    <p class="mb-0 mt-2">Un asesor te contactará por WhatsApp para acordar el horario. Tu cita se confirmará al hablar con él.</p>
                </div>
            @else
                <p class="small muted">Solo necesitamos tu nombre y WhatsApp. Un asesor te contactará para acordar el horario; no necesitas elegir asesor ni crear una cuenta.</p>
                @if($errors->any())<div class="alert alert-warning" role="alert">Revisa los datos indicados para enviar tu solicitud.</div>@endif
                <form action="{{ route('visits.store', $property['slug']) }}" method="post" data-visit-form>
                    @csrf
                    <input type="hidden" name="submission_token" value="{{ old('submission_token', (string) \Illuminate\Support\Str::uuid()) }}">
                    <div hidden aria-hidden="true"><label>Sitio web<input name="website" tabindex="-1" autocomplete="off"></label></div>
                    <div class="mb-3">
                        <label for="visitor-name" class="form-label">Tu nombre</label>
                        <input id="visitor-name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" autocomplete="name" maxlength="120" required @error('name') aria-describedby="visitor-name-error" aria-invalid="true" @enderror>
                        @error('name')<div class="invalid-feedback" id="visitor-name-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="visitor-phone" class="form-label">Tu WhatsApp</label>
                        <input id="visitor-phone" name="phone" type="tel" inputmode="tel" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" autocomplete="tel" maxlength="30" placeholder="10 dígitos" aria-describedby="visitor-phone-help @error('phone') visitor-phone-error @enderror" required @error('phone') aria-invalid="true" @enderror>
                        <div id="visitor-phone-help" class="form-text">México: 10 dígitos. Otro país: incluye + y código de país.</div>
                        @error('phone')<div class="invalid-feedback" id="visitor-phone-error">{{ $message }}</div>@enderror
                    </div>
                    @error('submission_token')<p class="text-danger small" role="alert">Recarga la página e intenta nuevamente.</p>@enderror
                    <p class="small muted">Usaremos estos datos para contactarte sobre tu solicitud de visita.</p>
                    <button class="btn btn-horizon w-100" type="submit">Enviar solicitud de visita</button>
                </form>
            @endif
        </div>
    </details>
@endif
