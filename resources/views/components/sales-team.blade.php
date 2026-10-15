<section id="equipo" class="sales-team section-space" aria-labelledby="sales-team-heading">
    <div class="container">
        <div class="row align-items-end g-4 mb-4 mb-lg-5">
            <div class="col-lg-7">
                <p class="eyebrow muted">Atención personal</p>
                <h2 id="sales-team-heading">Conoce a nuestros asesores</h2>
                <p class="muted mb-0 sales-team-intro">Un espacio para conocer a las personas encargadas de la venta de los terrenos y encontrar con quién resolver tus dudas.</p>
            </div>
            <div class="col-lg-5 text-lg-end">
                <span class="sales-team-pending">{{ $advisors->isEmpty() ? 'Presentación del equipo próximamente' : 'Personas que te acompañan en tu próxima visita' }}</span>
            </div>
        </div>

        <div class="row g-4">
            @forelse($advisors as $advisor)
                <div class="col-md-4"><article class="advisor-card h-100">
                    @if($advisor->photo_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($advisor->photo_path))
                        <img class="advisor-photo" src="{{ route('advisor-photo.show', $advisor) }}" alt="{{ $advisor->name }}, asesor de Horizonte" loading="lazy" width="480" height="480">
                    @else
                        <div class="advisor-portrait" aria-hidden="true"><svg viewBox="0 0 240 220" fill="none"><circle cx="120" cy="83" r="29" fill="currentColor" opacity=".25"/><path d="M61 177c0-34 26-58 59-58s59 24 59 58" fill="currentColor" opacity=".25"/></svg><span>Fotografía por incorporar</span></div>
                    @endif
                    <div class="advisor-copy"><p class="eyebrow muted mb-2">Asesoría en terrenos</p><h3>{{ $advisor->name }}</h3>
                    @if($advisor->areas)<p><strong>Zonas:</strong> {{ $advisor->areas }}</p>@endif
                    @if($advisor->bio)<p class="muted mb-0">{{ $advisor->bio }}</p>@endif
                    </div>
                </article></div>
            @empty
                <div class="col-12"><p class="muted">Próximamente podrás conocer aquí a nuestro equipo de asesores.</p></div>
            @endforelse
        </div>
        <p class="sales-team-note mb-0 mt-4">Solicita una visita desde la ficha del terreno. Nuestro equipo asignará al asesor que te acompañará.</p>
    </div>
</section>
