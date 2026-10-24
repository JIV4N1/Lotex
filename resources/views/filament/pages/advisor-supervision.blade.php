<x-filament-panels::page>
<section class="hz-welcome"><div><h2>Seguimiento del equipo</h2><p>Estado actual de todas las solicitudes, agrupadas por su responsable actual. Los perfiles públicos de asesores no son cuentas de acceso.</p></div></section>
<section class="hz-stat"><h2>Solicitudes abiertas sin asignar</h2><strong>{{ $unassigned }}</strong><a href="{{ \App\Filament\Resources\VisitRequestResource::getUrl() }}">Abrir bandeja de interesados →</a></section>
<x-filament::section heading="Carga de trabajo por responsable" description="Las citas pasadas requieren revisar el seguimiento; no indican que la visita se haya realizado. Cerrado no significa venta ni comisión.">
<div style="overflow-x:auto"><table class="hz-supervision-table"><caption class="sr-only">Solicitudes por responsable y estado actual</caption><thead><tr>@foreach(['Responsable','Total','Nuevos','Contactados','Próximas citas','Citas pasadas','Cerrados','Acción'] as $label)<th scope="col">{{ $label }}</th>@endforeach</tr></thead><tbody>
@forelse($advisors as $advisor)<tr><th scope="row">{{ $advisor->name }} @unless($advisor->is_admin)<small>(sin acceso administrativo)</small>@endunless</th>@foreach(['assigned_visits_count','new_count','contacted_count','upcoming_count','past_count','closed_count'] as $metric)<td>{{ $advisor->$metric }}</td>@endforeach<td><a href="{{ \App\Filament\Resources\VisitRequestResource::getUrl('index', ['filters'=>['assigned_to'=>['value'=>$advisor->id]]]) }}">Ver interesados de {{ $advisor->name }}</a></td></tr>
@empty<tr><td colspan="8">No hay responsables registrados.</td></tr>@endforelse
</tbody></table></div>
</x-filament::section>
</x-filament-panels::page>
