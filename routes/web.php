<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicSiteController;

Route::get('/', [PublicSiteController::class, 'home'])->name('home');
Route::get('/terrenos', [PublicSiteController::class, 'index'])->name('properties.index');
Route::get('/terrenos/{slug}', [PublicSiteController::class, 'show'])->name('properties.show');
Route::view('/nosotros', 'about')->name('about');
Route::get('/contacto', [PublicSiteController::class, 'contact'])->name('contact');
