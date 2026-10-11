@extends('layouts.public')
@section('title', 'Maqueta de administración')
@section('content')
<div class="container section-space">
    <p class="eyebrow muted">Horizonte · Espacio de trabajo</p>
    <h1>Prepara tu próximo terreno</h1>
    <p class="muted mb-4">Maqueta administrativa local. Prueba la captura y la vista previa sin publicar ni guardar información.</p>
    <div class="demo-notice mb-4"><strong>Solo demostración</strong><p class="mb-0">No es el panel privado. No introduzcas datos personales ni documentos reales. Los archivos seleccionados permanecen en este navegador y los cambios se pierden al recargar. El acceso administrativo real sigue en <a href="/admin">/admin</a>.</p></div>
    <div class="admin-demo-grid">
        <aside class="admin-demo-sidebar"><p class="eyebrow">Nuevo terreno</p><a href="#informacion">01 · Información</a><a href="#localizacion">02 · Ubicación</a><a href="#caracteristicas">03 · Características</a><a href="#archivos">04 · Fotografías y documentos</a><a href="#publicacion">05 · Publicación</a></aside>
        <div>
            <noscript><p>Activa JavaScript para probar el editor local.</p></noscript><form id="property-demo-editor"><fieldset data-demo-fields disabled>
                <section class="editor-section" id="informacion"><h2>01. Información principal</h2><div class="row g-3">
                    @foreach(['title' => ['Título del terreno', 'text'], 'code' => ['Clave interna', 'text'], 'slug' => ['Slug (dirección de la ficha)', 'text'], 'price' => ['Precio MXN', 'number'], 'area' => ['Superficie m²', 'number'], 'frontage' => ['Frente en metros (opcional)', 'number'], 'depth' => ['Fondo en metros (opcional)', 'number']] as $key => [$label, $type])
                    <div class="{{ $key === 'title' ? 'col-12' : 'col-md-6' }}"><label class="form-label" for="editor-{{ $key }}">{{ $label }}</label><input class="form-control" id="editor-{{ $key }}" name="{{ $key }}" type="{{ $type }}" @if($type === 'number') step="0.01" min="{{ $key === 'price' ? '0' : '0.01' }}" @else maxlength="180" @endif @required(in_array($key, ['title', 'code', 'slug', 'price', 'area']))></div>
                    @endforeach
                    <div class="col-12"><label class="form-label" for="editor-description">Descripción</label><textarea class="form-control" id="editor-description" name="description" rows="4" maxlength="5000"></textarea></div>
                </div></section>
                <section class="editor-section" id="localizacion"><h2>02. Ubicación</h2><div class="row g-3">
                    @foreach(['state' => 'Estado', 'municipality' => 'Municipio', 'zone' => 'Zona', 'address' => 'Dirección de referencia'] as $key => $label)
                    <div class="col-md-6"><label class="form-label" for="editor-{{ $key }}">{{ $label }}</label><input class="form-control" id="editor-{{ $key }}" name="{{ $key }}" maxlength="200" @required($key !== 'address')></div>
                    @endforeach
                    <div class="col-md-6"><label class="form-label" for="editor-latitude">Latitud (opcional)</label><input class="form-control" id="editor-latitude" name="latitude" type="number" min="-90" max="90" step="0.0000001"></div>
                    <div class="col-md-6"><label class="form-label" for="editor-longitude">Longitud (opcional)</label><input class="form-control" id="editor-longitude" name="longitude" type="number" min="-180" max="180" step="0.0000001"></div>
                    <p class="small muted mb-0">La ubicación exacta se generará a partir de coordenadas verificadas. Los selectores de ubicaciones se conectarán al catálogo de la base de datos más adelante.</p>
                </div></section>
                <section class="editor-section" id="caracteristicas"><h2>03. Características y condiciones</h2><div class="row g-3">
                    @foreach(['services' => 'Servicios disponibles', 'access' => 'Acceso', 'topography' => 'Topografía', 'land_use' => 'Uso de suelo', 'documents' => 'Resumen documental', 'payment_terms' => 'Condiciones de pago'] as $key => $label)
                    <div class="col-md-6"><label class="form-label" for="editor-{{ $key }}">{{ $label }}</label><textarea class="form-control" id="editor-{{ $key }}" name="{{ $key }}" rows="3" maxlength="2000"></textarea></div>
                    @endforeach
                </div></section>
                <section class="editor-section" id="archivos"><h2>04. Fotografías y documentos</h2>
                    <div class="upload-demo mb-3"><label class="form-label" for="editor-images">Seleccionar fotografías ilustrativas</label><input class="form-control" id="editor-images" type="file" accept="image/jpeg,image/png,image/webp" multiple aria-describedby="photo-help"><p class="small muted mt-2 mb-0" id="photo-help">JPG, PNG o WebP. La primera imagen será la portada. En esta maqueta solo se previsualiza; no se sube al servidor.</p></div>
                    <label class="form-label" for="editor-alt">Texto alternativo de la portada</label><input class="form-control mb-3" id="editor-alt" name="alt" maxlength="250">
                    <div class="upload-demo"><label class="form-label" for="editor-document">Seleccionar un PDF de ejemplo</label><input class="form-control" id="editor-document" type="file" accept="application/pdf"><p class="small muted mt-2 mb-0">Solo se mostrará el nombre del archivo. No se publica ni se acredita su validez.</p></div>
                    <div class="row g-3 mt-1">@foreach(['document_type' => 'Tipo de documento', 'document_issuer' => 'Emisor', 'document_date' => 'Fecha del documento'] as $key => $label)<div class="col-md-4"><label class="form-label" for="editor-{{ $key }}">{{ $label }}</label><input class="form-control" id="editor-{{ $key }}" name="{{ $key }}" type="{{ $key === 'document_date' ? 'date' : 'text' }}"></div>@endforeach</div>
                </section>
                <section class="editor-section" id="publicacion"><h2>05. Estado de la ficha</h2><div class="row g-3">
                    <div class="col-md-6"><label class="form-label" for="editor-publication">Publicación</label><select class="form-select" id="editor-publication" name="publication"><option>Borrador</option><option>Publicado</option></select></div>
                    <div class="col-md-6"><label class="form-label" for="editor-availability">Disponibilidad</label><select class="form-select" id="editor-availability" name="availability"><option>Disponible</option><option>Apartado</option><option>Vendido</option></select></div>
                    <div class="col-12"><div class="form-check"><input class="form-check-input" id="editor-featured" name="featured" type="checkbox"><label class="form-check-label" for="editor-featured">Mostrar como destacado</label></div></div>
                </div><p class="small muted mt-3">Cambiar estos valores no publica la ficha ni modifica el catálogo.</p><div class="editor-actions"><button class="btn btn-horizon" type="submit">Generar vista previa</button><button class="btn btn-outline-horizon" type="reset">Limpiar campos</button><button class="btn btn-outline-horizon" type="button" disabled>Guardar · Próximamente</button></div></section>
            </fieldset></form>
            <section class="admin-preview" id="editor-preview" hidden tabindex="-1" aria-labelledby="preview-heading"><p class="eyebrow">Vista previa · No guardada</p><img class="preview-image" id="preview-image" hidden alt=""><h2 id="preview-heading"></h2><p id="preview-location"></p><p id="preview-price"></p><dl class="detail-list" id="preview-details"></dl><p class="small muted mb-0">Esta ficha no existe en el catálogo. Al recargar se descarta.</p></section>
        </div>
    </div>
</div>
@endsection

