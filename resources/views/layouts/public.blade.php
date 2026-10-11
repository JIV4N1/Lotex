<!DOCTYPE html>
<html lang="es-MX">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Explore el prototipo de Inmobiliaria Horizonte: terrenos ficticios en Puebla e imágenes ilustrativas.">
<title>@yield('title', 'Inicio') | Inmobiliaria Horizonte</title>
@vite(['resources/css/public.css', 'resources/js/public.js'])
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<div class="demo-banner">Prototipo de demostración · Terrenos y precios ficticios</div>
<x-navigation />
<main id="contenido" tabindex="-1">@yield('content')</main>
<footer class="site-footer"><div class="container"><div class="row gy-4 pb-5">
<div class="col-md-6"><p class="footer-brand">Horizonte</p><p class="footer-note">El espacio donde comienzan tus ideas.</p><p class="small mt-3">Marca provisional · Puebla, México</p></div>
<div class="col-6 col-md-3"><p class="eyebrow">Explora</p><a href="{{ route('properties.index') }}">Terrenos</a><a href="{{ route('about') }}">Nosotros</a></div>
<div class="col-6 col-md-3"><p class="eyebrow">Conversemos</p><a href="{{ route('contact') }}">Contacto demostrativo</a><span class="small">Datos de contacto por confirmar.</span></div>
</div><div class="footer-bottom d-flex flex-wrap justify-content-between gap-3"><span>© {{ date('Y') }} Inmobiliaria Horizonte</span><span>Fotografías ilustrativas. No representan terrenos en venta.</span></div></div></footer>
</body></html>
