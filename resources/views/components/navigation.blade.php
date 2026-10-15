<header class="site-header">
    <nav class="navbar navbar-expand-md py-3" aria-label="Navegación principal">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}" aria-label="Horizonte, Inicio">
                <svg class="brand-symbol" viewBox="0 0 40 40" fill="none" aria-hidden="true"><path d="M3 31h34M5 25 20 9l15 16M12 25l8-8 8 8" stroke="currentColor" stroke-width="1.5"/></svg>
                <span class="brand-name">Horizonte</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#public-navigation" aria-controls="public-navigation" aria-expanded="false" aria-label="Abrir o cerrar navegación"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="public-navigation">
                <ul class="navbar-nav ms-auto gap-md-4 mt-3 mt-md-0 align-items-md-center">
                    @foreach(['home' => 'Inicio', 'properties.index' => 'Terrenos', 'about' => 'Nosotros', 'contact' => 'Contacto'] as $name => $label)
                        @php($active = $name === 'properties.index' ? request()->routeIs('properties.*') : request()->routeIs($name))
                        <li class="nav-item"><a class="nav-link {{ $active ? 'active' : '' }}" href="{{ route($name) }}" @if($active) aria-current="page" @endif>{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </nav>
</header>
