<section id="equipo" class="sales-team section-space" aria-labelledby="sales-team-heading">
    <div class="container">
        <div class="row align-items-end g-4 mb-4 mb-lg-5">
            <div class="col-lg-7">
                <p class="eyebrow muted">Atención personal</p>
                <h2 id="sales-team-heading">Conoce a nuestros asesores</h2>
                <p class="muted mb-0 sales-team-intro">Un espacio para conocer a las personas encargadas de la venta de los terrenos y encontrar con quién resolver tus dudas.</p>
            </div>
            <div class="col-lg-5 text-lg-end">
                <span class="sales-team-pending">Presentación del equipo próximamente</span>
            </div>
        </div>

        <div class="row g-4">
            @foreach(range(1, 3) as $slot)
                <div class="col-md-4">
                    <article class="advisor-card h-100" aria-label="Espacio para asesor {{ $slot }}, pendiente de completar">
                        <div class="advisor-portrait" aria-hidden="true">
                            <svg viewBox="0 0 240 220" fill="none" focusable="false">
                                <circle cx="120" cy="102" r="76" stroke="currentColor" opacity=".2" />
                                <circle cx="120" cy="83" r="29" fill="currentColor" opacity=".25" />
                                <path d="M61 177c0-34 26-58 59-58s59 24 59 58" fill="currentColor" opacity=".25" />
                                <path d="M30 196h180" stroke="currentColor" opacity=".3" />
                            </svg>
                            <span>Fotografía por incorporar</span>
                        </div>
                        <div class="advisor-copy">
                            <p class="eyebrow muted mb-2">Asesoría inmobiliaria</p>
                            <h3>Perfil por incorporar</h3>
                            <p class="muted mb-0">Aquí aparecerán el nombre, las zonas de atención y la presentación del asesor.</p>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>
        <p class="sales-team-note mb-0 mt-4">Vista provisional: estos espacios todavía no representan perfiles reales. Los datos del equipo están pendientes de incorporar.</p>
    </div>
</section>
