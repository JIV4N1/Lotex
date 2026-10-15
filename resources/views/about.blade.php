@extends('layouts.public')
@section('title', 'Nosotros')
@section('content')
<section class="container section-space" aria-labelledby="about-title">
    <div class="row g-5 align-items-center">
        <div class="col-lg-6">
            <p class="eyebrow muted">Acerca de Horizonte</p>
            <h1 id="about-title">Un lugar para encontrar<br> tu próximo comienzo.</h1>
            <p class="lead mt-4">Conectamos a personas interesadas en terrenos con quienes los ofrecen, con información a la mano y atención cercana.</p>
            <p class="muted">Horizonte es una plataforma de intermediación. Aquí puedes explorar terrenos, comparar sus características y solicitar una visita. Nuestro equipo organiza el contacto para que puedas conocer el lugar y resolver tus dudas.</p>
            <div class="d-flex flex-wrap gap-3 mt-4"><a class="btn btn-horizon" href="{{ route('properties.index') }}">Explorar terrenos</a><a class="btn btn-outline-horizon" href="{{ route('advisors') }}">Conocer a los asesores</a></div>
        </div>
        <div class="col-lg-6">
            <figure class="mb-0"><img class="about-landscape" src="{{ asset('images/demo/campo.jpg') }}" alt="Paisaje de campos y colinas" width="1200" height="800"><figcaption class="small muted mt-2">Fotografía ilustrativa; no corresponde a un terreno del catálogo.</figcaption></figure>
        </div>
    </div>
</section>
<section class="zones-section section-space" aria-labelledby="about-process">
    <div class="container">
        <p class="eyebrow muted">Así te acompañamos</p><h2 id="about-process">Del primer vistazo a la visita</h2>
        <div class="row g-4 mt-3">
            @foreach([
                ['Explora con información', 'Consulta la ubicación, el precio, las medidas y las características que se han publicado de cada terreno.'],
                ['Cuéntanos cuál te interesa', 'Pulsa «Quiero visitar este terreno» en su ficha. Solo necesitas dejar tu nombre y WhatsApp; la referencia se incluye automáticamente.'],
                ['Acuerda tu visita', 'Un asesor te contactará para resolver tus dudas y acordar el día y la hora. La solicitud por sí sola no confirma una cita.']
            ] as [$title, $description])
                <div class="col-md-4"><span class="step-number" aria-hidden="true">0{{ $loop->iteration }}</span><h3 class="h5 mt-4">{{ $title }}</h3><p class="muted mb-0">{{ $description }}</p></div>
            @endforeach
        </div>
    </div>
</section>
<section class="container section-space">
    <div class="row g-4 align-items-start">
        <div class="col-lg-5"><p class="eyebrow muted">Claridad en cada paso</p><h2>Una decisión que merece buenas preguntas.</h2></div>
        <div class="col-lg-7"><p>Facilitamos la información y el contacto. La disponibilidad, la documentación y las condiciones de cada operación deben confirmarse con los responsables del terreno antes de tomar una decisión.</p><p class="muted">El sitio no recibe pagos ni apartados en línea. Publicar un documento no sustituye su revisión ni acredita por sí mismo la situación jurídica del terreno.</p><a class="text-link" href="{{ route('contact') }}">¿Tienes alguna duda? Escríbenos →</a></div>
    </div>
</section>
@endsection
