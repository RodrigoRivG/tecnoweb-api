<?php

use App\Http\Controllers\Api\V1\AutenticacionController;
use App\Http\Controllers\Api\V1\Egresado\DocumentoController;
use App\Http\Controllers\Api\V1\Egresado\PostulacionController;
use App\Http\Controllers\Api\V1\Publico\BienvenidaController;
use App\Http\Controllers\Api\V1\RegistroEgresadoController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('publico')->middleware('throttle:publico')->name('publico.')->group(function () {
        Route::get('facultades', [BienvenidaController::class, 'facultades'])->name('facultades');
        Route::get('convocatorias', [BienvenidaController::class, 'convocatorias'])->name('convocatorias');
        Route::get('perfiles', [BienvenidaController::class, 'perfiles'])->name('perfiles');
    });

    Route::post('auth/login', [AutenticacionController::class, 'login'])->middleware('throttle:login')->name('auth.login');
    Route::post('registro/egresado', RegistroEgresadoController::class)->middleware('throttle:registro')->name('registro.egresado');

    Route::middleware(['auth:sanctum', 'throttle:autenticado'])->group(function () {
        Route::post('auth/logout', [AutenticacionController::class, 'logout'])->name('auth.logout');
        Route::get('auth/me', [AutenticacionController::class, 'me'])->name('auth.me');

        Route::prefix('egresado')->middleware('rol:EGRESADO')->name('egresado.')->group(function () {
            Route::get('convocatorias', [PostulacionController::class, 'convocatorias'])->name('convocatorias');
            Route::get('postulaciones', [PostulacionController::class, 'index'])->name('postulaciones.index');
            Route::post('postulaciones', [PostulacionController::class, 'store'])->name('postulaciones.store');
            Route::get('postulaciones/{postulacion}', [PostulacionController::class, 'show'])->whereNumber('postulacion')->name('postulaciones.show');
            Route::post('postulaciones/{postulacion}/documentos', [DocumentoController::class, 'store'])->whereNumber('postulacion')->name('documentos.store');
            Route::get('documentos/{documento}/archivo', [DocumentoController::class, 'archivo'])->whereNumber('documento')->name('documentos.archivo');
        });
    });
});
