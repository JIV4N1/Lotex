@php
    $segments = request()->segments();
    $fallback = count($segments) > 2
        ? url(implode('/', array_slice($segments, 0, 2)))
        : \App\Filament\Pages\Dashboard::getUrl();
@endphp
@if(! request()->routeIs('filament.*.pages.dashboard'))
    <nav class="hz-back-navigation" aria-label="Navegación de regreso">
        <a href="{{ $fallback }}" class="hz-back-link" x-data x-on:click="
            if (document.referrer && window.history.length > 1) {
                const previous = new URL(document.referrer);
                if (previous.origin === location.origin && previous.pathname.startsWith('/{{ \Filament\Facades\Filament::getCurrentPanel()->getPath() }}/') && !previous.pathname.endsWith('/login') && previous.href !== location.href) {
                    $event.preventDefault();
                    window.history.back();
                }
            }
        ">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m12 5-7 7 7 7M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span>Volver</span>
        </a>
    </nav>
@endif
