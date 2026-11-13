<?php

use App\Http\Controllers\Api\V1\Admin\CarreraController;
use App\Http\Controllers\Api\V1\Admin\ConvocatoriaAdminController;
use App\Http\Controllers\Api\V1\Admin\FacultadController;
use App\Http\Controllers\Api\V1\Admin\PreguntaAdminController;
use App\Http\Controllers\Api\V1\Admin\UsuarioAdminController;
use App\Http\Controllers\Api\V1\AutenticacionController;
use App\Http\Controllers\Api\V1\Egresado\DocumentoController;
use App\Http\Controllers\Api\V1\Egresado\EvaluacionController;
use App\Http\Controllers\Api\V1\Egresado\PostulacionController;
use App\Http\Controllers\Api\V1\Evaluador\PostulacionEvaluadorController;
use App\Http\Controllers\Api\V1\Evaluador\RevisionCvController;
use App\Http\Controllers\Api\V1\Evaluador\RevisionEntrevistaController;
use App\Http\Controllers\Api\V1\Evaluador\RevisionRequisitosController;
use App\Http\Controllers\Api\V1\Evaluador\SeleccionController;
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

        // Expediente y archivos compartidos (Postulante dueño o Evaluador ADMIN/AYUDANTE)
        Route::get('postulaciones/{postulacion}', [PostulacionController::class, 'show'])
            ->whereNumber('postulacion')
            ->name('postulaciones.show');
        Route::get('documentos/{documento}/archivo', [DocumentoController::class, 'archivo'])
            ->whereNumber('documento')
            ->name('documentos.archivo');
        Route::get('postulaciones/{postulacion}/resultado-final', [SeleccionController::class, 'resultadoPostulante'])
            ->whereNumber('postulacion')
            ->name('postulaciones.resultado-final');

        Route::prefix('egresado')->middleware('rol:EGRESADO')->name('egresado.')->group(function () {
            Route::get('convocatorias', [PostulacionController::class, 'convocatorias'])->name('convocatorias');
            Route::get('postulaciones', [PostulacionController::class, 'index'])->name('postulaciones.index');
            Route::post('postulaciones', [PostulacionController::class, 'store'])->name('postulaciones.store');
            Route::get('postulaciones/{postulacion}', [PostulacionController::class, 'show'])->whereNumber('postulacion')->name('postulaciones.show');
            Route::post('postulaciones/{postulacion}/documentos', [DocumentoController::class, 'store'])->whereNumber('postulacion')->name('documentos.store');
            Route::get('documentos/{documento}/archivo', [DocumentoController::class, 'archivo'])->whereNumber('documento')->name('documentos.archivo');

            // Evaluación en línea (Etapa 4 - Psicológica / Tests)
            Route::get('postulaciones/{postulacion}/evaluacion-activa', [EvaluacionController::class, 'evaluacionActiva'])
                ->whereNumber('postulacion')
                ->name('evaluacion.activa');
            Route::post('postulaciones/{postulacion}/iniciar-evaluacion', [EvaluacionController::class, 'iniciar'])
                ->whereNumber('postulacion')
                ->name('evaluacion.iniciar');
            Route::get('intentos/{intento}/preguntas', [EvaluacionController::class, 'preguntas'])
                ->whereNumber('intento')
                ->name('intentos.preguntas');
            Route::post('intentos/{intento}/responder', [EvaluacionController::class, 'responder'])
                ->whereNumber('intento')
                ->name('intentos.responder');
            Route::post('intentos/{intento}/finalizar', [EvaluacionController::class, 'finalizar'])
                ->whereNumber('intento')
                ->name('intentos.finalizar');
            Route::get('postulaciones/{postulacion}/resultado-final', [SeleccionController::class, 'resultadoPostulante'])
                ->whereNumber('postulacion')
                ->name('postulaciones.resultado-final');
        });

        Route::prefix('evaluador')->middleware('rol:ADMIN,AYUDANTE')->name('evaluador.')->group(function () {
            Route::get('postulaciones', [PostulacionEvaluadorController::class, 'index'])
                ->name('postulaciones.index');

            Route::get('postulaciones/{postulacion}', [PostulacionController::class, 'show'])
                ->whereNumber('postulacion')
                ->name('postulaciones.show-evaluador');

            Route::get('documentos/{documento}/archivo', [DocumentoController::class, 'archivo'])
                ->whereNumber('documento')
                ->name('documentos.archivo-evaluador');

            Route::post('postulaciones/{postulacion}/aprobar-requisitos', [RevisionRequisitosController::class, 'aprobar'])
                ->whereNumber('postulacion')
                ->name('postulaciones.aprobar-requisitos');

            // Etapa 6: Análisis de CV (Rúbrica institucional)
            Route::get('postulaciones/{postulacion}/analisis-cv', [RevisionCvController::class, 'formulario'])
                ->whereNumber('postulacion')
                ->name('postulaciones.analisis-cv');
            Route::post('postulaciones/{postulacion}/calificar-cv', [RevisionCvController::class, 'calificar'])
                ->whereNumber('postulacion')
                ->name('postulaciones.calificar-cv');

            // Etapa 7: Entrevista (Rúbrica institucional)
            Route::get('postulaciones/{postulacion}/entrevista', [RevisionEntrevistaController::class, 'formulario'])
                ->whereNumber('postulacion')
                ->name('postulaciones.entrevista');
            Route::post('postulaciones/{postulacion}/calificar-entrevista', [RevisionEntrevistaController::class, 'calificar'])
                ->whereNumber('postulacion')
                ->name('postulaciones.calificar-entrevista');

            // Etapa 8: Selección final y Adjudicación
            Route::get('procesos/{proceso}/ranking', [SeleccionController::class, 'ranking'])
                ->whereNumber('proceso')
                ->name('procesos.ranking');
            Route::post('procesos/{proceso}/adjudicar', [SeleccionController::class, 'adjudicar'])
                ->whereNumber('proceso')
                ->name('procesos.adjudicar');
            Route::get('postulaciones/{postulacion}/resultado-final', [SeleccionController::class, 'resultadoPostulante'])
                ->whereNumber('postulacion')
                ->name('postulaciones.resultado-final-evaluador');
        });

        Route::prefix('admin')->middleware('rol:ADMIN')->name('admin.')->group(function () {
            Route::apiResource('facultades', FacultadController::class)
                ->parameters(['facultades' => 'facultad'])
                ->whereNumber('facultad');

            Route::apiResource('carreras', CarreraController::class)
                ->parameters(['carreras' => 'carrera'])
                ->whereNumber('carrera');

            // Gestión de Convocatorias
            Route::apiResource('convocatorias', ConvocatoriaAdminController::class)
                ->parameters(['convocatorias' => 'proceso'])
                ->whereNumber('proceso');

            // Banco de Preguntas y Pruebas Psicotécnicas
            Route::get('banco-preguntas/catalogo', [PreguntaAdminController::class, 'catalogoPruebas'])->name('banco-preguntas.catalogo');
            Route::apiResource('preguntas', PreguntaAdminController::class)
                ->parameters(['preguntas' => 'pregunta'])
                ->whereNumber('pregunta');

            // Gestión de Usuarios y Permisos (RBAC)
            Route::get('usuarios', [UsuarioAdminController::class, 'index'])->name('usuarios.index');
            Route::post('usuarios', [UsuarioAdminController::class, 'store'])->name('usuarios.store');
            Route::put('usuarios/{usuario}', [UsuarioAdminController::class, 'update'])->whereNumber('usuario')->name('usuarios.update');
            Route::get('roles-permisos', [UsuarioAdminController::class, 'rolesPermisos'])->name('roles-permisos');
            Route::put('roles/{rol}/permisos', [UsuarioAdminController::class, 'actualizarPermisosRol'])->whereNumber('rol')->name('roles.permisos.update');
        });
    });
});
