@extends('layouts.public')
@section('title', 'Contacto')
@section('content')
<section class="container section-space"><div class="row g-5"><div class="col-lg-5"><p class="eyebrow muted">Hablemos de tu próximo espacio</p><h1>Tu idea empieza<br>con una conversación.</h1><p class="lead mt-4">Cuéntanos qué necesitas. Nuestro equipo recibirá tu consulta y se pondrá en contacto contigo.</p><p class="muted">¿Ya elegiste un terreno y quieres conocerlo? Solicita una visita desde su ficha.</p><a href="{{ route('properties.index') }}" class="text-link">Explorar terrenos →</a>
@if($property)<div class="mt-4"><h2 class="h4">{{ $property['title'] }}</h2><p>{{ $property['code'] }} · {{ $property['zone'] }}</p><a href="{{ route('properties.show', $property['slug']) }}">Volver al terreno →</a></div>@endif
</div><div class="col-lg-7">
@if(session('contact_sent'))<div class="alert alert-success" role="status"><strong>Recibimos tu mensaje.</strong><p class="mb-0">El equipo revisará tu consulta y te contactará mediante los datos que proporcionaste.</p></div>@endif
<form class="contact-form" action="{{ route('contact.store') }}" method="post" aria-labelledby="form-title">
@csrf
<h2 id="form-title" class="h3 mb-4">Envíanos tu consulta</h2>
@if($errors->any())<div class="alert alert-warning" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<input type="hidden" name="submission_token" value="{{ old('submission_token', (string) \Illuminate\Support\Str::uuid()) }}">
<input type="hidden" name="property_slug" value="{{ old('property_slug', $property['slug'] ?? '') }}">
<div hidden aria-hidden="true"><label>Sitio web<input name="website" tabindex="-1" autocomplete="off"></label></div>
<div class="row g-4">
<div class="col-md-6"><label for="contact-name" class="form-label">Nombre</label><input id="contact-name" name="name" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="contact-name-error" class="form-control" autocomplete="name" maxlength="120" value="{{ old('name') }}" required><x-field-error name="name" id="contact-name-error" /></div>
<div class="col-md-6"><label for="contact-phone" class="form-label">Teléfono / WhatsApp</label><input type="tel" id="contact-phone" name="phone" aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}" class="form-control" autocomplete="tel" maxlength="30" value="{{ old('phone') }}" aria-describedby="contact-phone-help contact-phone-error" required><small id="contact-phone-help" class="muted">México: 10 dígitos. Otro país: incluye + y código de país.</small><x-field-error name="phone" id="contact-phone-error" /></div>
<div class="col-12"><label for="contact-email" class="form-label">Correo electrónico (opcional)</label><input type="email" id="contact-email" name="email" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="contact-email-error" class="form-control" autocomplete="email" maxlength="255" value="{{ old('email') }}"><x-field-error name="email" id="contact-email-error" /></div>
<div class="col-12"><label for="contact-message" class="form-label">Mensaje</label><textarea id="contact-message" name="message" aria-invalid="{{ $errors->has('message') ? 'true' : 'false' }}" aria-describedby="contact-message-error" class="form-control" rows="5" minlength="10" maxlength="5000" required>{{ old('message', $property ? 'Me gustaría conocer más sobre '.$property['title'].' ('.$property['code'].').' : '') }}</textarea><x-field-error name="message" id="contact-message-error" /></div>
<div class="col-12"><p class="small muted">Usaremos tus datos para atender esta consulta. No incluyas documentos ni información bancaria.</p><button type="submit" class="btn btn-horizon w-100">Enviar mensaje</button></div>
</div></form></div></div></section>
@endsection
