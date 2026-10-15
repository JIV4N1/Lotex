<x-filament-panels::page>
    <section class="hz-welcome">
        <div><span class="hz-eyebrow">HORIZONTE</span><h2>Tu equipo, con todo a la vista.</h2><p>Administra los terrenos y acompaña cada solicitud hasta su próxima visita.</p></div>
        <a class="hz-action" href="{{ \App\Filament\Resources\PropertyResource::getUrl('create') }}">+ Dar de alta un terreno</a>
    </section>
    <div class="hz-stats" aria-label="Resumen de actividad">
        @foreach([['Terrenos publicados', $published, 'Visibles en el catálogo'], ['Borradores', $drafts, 'Pendientes de publicación'], ['Interesados sin asignar', $unassigned, 'Solicitudes abiertas por atender'], ['Próximas citas', $appointments, 'Horarios confirmados']] as [$label, $count, $hint])
            <section class="hz-stat"><h2>{{ $label }}</h2><strong>{{ $count }}</strong><p>{{ $hint }}</p></section>
        @endforeach
    </div>
    <div class="hz-workspace">
        <section class="hz-card"><header><div><span class="hz-eyebrow">SEGUIMIENTO COMERCIAL</span><h2>Últimos interesados</h2></div><a href="{{ \App\Filament\Resources\VisitRequestResource::getUrl() }}">Ver bandeja →</a></header>
            @forelse($recent as $request)
                <a class="hz-lead" href="{{ \App\Filament\Resources\VisitRequestResource::getUrl('edit', ['record' => $request]) }}"><div><strong>{{ $request->name }}</strong><span>{{ $request->property?->title ?? 'Terreno no disponible' }}</span><small>{{ $request->created_at->timezone('America/Mexico_City')->format('d/m/Y · H:i') }}</small></div><div class="hz-lead-status"><span>{{ \App\Enums\VisitStatus::options()[$request->status->value] }}</span><small>{{ $request->assignee?->name ?? 'Sin asignar' }}</small></div></a>
            @empty
                <div class="hz-empty"><h3>Todo listo para recibir interesados</h3><p>Las solicitudes de visita aparecerán aquí. Podrás asignar un responsable y dar seguimiento desde la bandeja.</p></div>
            @endforelse
        </section>
        <section class="hz-card"><header><div><span class="hz-eyebrow">TU ESPACIO DE TRABAJO</span><h2>Accesos rápidos</h2></div></header>
            <a class="hz-shortcut" href="{{ \App\Filament\Resources\PropertyResource::getUrl() }}"><strong>Gestionar terrenos →</strong><span>Precios, fotografías, documentos y publicación.</span></a>
            <a class="hz-shortcut" href="{{ \App\Filament\Resources\ZoneResource::getUrl() }}"><strong>Organizar ubicaciones →</strong><span>Estados, municipios y zonas del catálogo.</span></a>
            <a class="hz-shortcut" href="{{ route('home') }}" target="_blank" rel="noopener"><strong>Ver sitio público ↗</strong><span>Consulta cómo ven tus clientes el catálogo.</span></a>
        </section>
    </div>
</x-filament-panels::page>
