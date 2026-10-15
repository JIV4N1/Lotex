@extends('layouts.public')
@section('title', 'Asesores')
@section('content')
<section class="container pt-5 pb-2" aria-labelledby="advisors-title">
    <p class="eyebrow muted">El equipo de Horizonte</p>
    <h1 id="advisors-title">Una persona para acompañar<br class="d-none d-md-block"> tu próxima visita.</h1>
    <p class="lead mt-4 col-lg-8">Conoce a nuestros asesores y las zonas que atienden. Si un terreno te interesa, solicita una visita desde su ficha: el equipo asignará a la persona que te contactará para acordar el horario.</p>
</section>
<x-sales-team />
<section class="container pb-5"><div class="contact-strip"><div><p class="eyebrow">El siguiente paso</p><h2>Encuentra el terreno que quieres conocer.</h2></div><a class="btn btn-light" href="{{ route('properties.index') }}">Explorar terrenos →</a></div></section>
@endsection
