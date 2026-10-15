<!DOCTYPE html>
<html lang="es-MX">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Explora los terrenos publicados de Horizonte: ubicación, características y documentación.">
<title>@yield('title', 'Inicio') | Horizonte</title>
@vite(['resources/css/public.css', 'resources/js/public.js'])
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<div class="demo-banner">Horizonte · Encuentra tu próximo espacio</div>
<x-navigation />
<main id="contenido" tabindex="-1">@yield('content')</main>
<footer class="site-footer"><div class="container"><div class="row gy-4 pb-5">
<div class="col-md-6"><p class="footer-brand">Horizonte</p><p class="footer-note">El espacio donde comienzan tus ideas.</p><p class="small mt-3">Lotificaciones en Venta · Puebla, México</p></div>
<div class="col-6 col-md-3"><p class="eyebrow">Explora</p><a href="{{ route('properties.index') }}">Terrenos</a><a href="{{ route('about') }}">Nosotros</a></div>
<div class="col-6 col-md-3"><p class="eyebrow">Conversemos</p><a href="{{ route('contact') }}">Contacto</a><a href="{{ route('advisors') }}">Asesores</a></div>
</div><div class="footer-bottom d-flex flex-wrap justify-content-between gap-3"><span>© {{ date('Y') }} Horizonte</span><span>Consulta disponibilidad y condiciones de cada propiedad.</span></div></div></footer>
@unless(request()->routeIs('prototype.*'))<x-chatbot />@endunless
</body></html>
