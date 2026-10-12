<?php

use App\Http\Controllers\PropertyFileController;
use App\Http\Controllers\PublicSiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicSiteController::class, 'home'])->name('home');
Route::get('/terrenos', [PublicSiteController::class, 'index'])->name('properties.index');
Route::get('/terrenos/{slug}', [PublicSiteController::class, 'show'])->name('properties.show');
Route::view('/nosotros', 'about')->name('about');
Route::get('/contacto', [PublicSiteController::class, 'contact'])->name('contact');

// Local-only design preview. This is not an alternative authentication path to Filament.
Route::get('/prototipo/administracion', function () {
    abort_unless(app()->environment('local'), 404);

    return view('prototype.admin');
})->name('prototype.admin');

Route::get('/archivos/imagenes/{image}', [PropertyFileController::class, 'image'])->whereNumber('image')->name('property-images.show');
Route::get('/archivos/documentos/{document}', [PropertyFileController::class, 'document'])->whereNumber('document')->name('property-documents.show');
