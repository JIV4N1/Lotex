<x-filament-panels::page>
<x-filament::section heading="Información pública" description="Estos datos aparecerán en el pie de página. Deja vacíos los contactos que aún no estén confirmados.">
<form wire:submit="save" class="space-y-6">
@foreach(['footer_text'=>'Presentación breve', 'coverage'=>'Zona de atención', 'contact_email'=>'Correo de contacto', 'contact_phone'=>'Teléfono de contacto', 'office_hours'=>'Horario de atención'] as $key=>$label)
<div><label for="setting-{{ $key }}">{{ $label }}</label><x-filament::input.wrapper><x-filament::input id="setting-{{ $key }}" wire:model="settings.{{ $key }}" :type="$key === 'contact_email' ? 'email' : 'text'" /></x-filament::input.wrapper>@error('settings.'.$key)<p role="alert">{{ $message }}</p>@enderror</div>
@endforeach
<x-filament::button type="submit" wire:loading.attr="disabled" wire:target="save">Guardar configuración</x-filament::button>
</form>
</x-filament::section>
</x-filament-panels::page>
