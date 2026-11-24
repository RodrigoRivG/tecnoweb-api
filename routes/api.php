<?php

use App\Http\Controllers\Api\V1\Admin\CarreraController;
use App\Http\Controllers\Api\V1\Admin\ConvocatoriaAdminController;
use App\Http\Controllers\Api\V1\Admin\FacultadController;
use App\Http\Controllers\Api\V1\Admin\PerfilIdealController;
use App\Http\Controllers\Api\V1\Admin\PreguntaAdminController;
use App\Http\Controllers\Api\V1\Admin\SolicitudEmpresaController;
use App\Http\Controllers\Api\V1\Admin\UsuarioAdminController;
use App\Http\Controllers\Api\V1\Empresa\PanelEmpresaController;
use App\Http\Controllers\Api\V1\Empresa\RegistroSolicitudController;
use App\Http\Controllers\Api\V1\AutenticacionController;
use App\Http\Controllers\Api\V1\Egresado\DocumentoController;
use App\Http\Controllers\Api\V1\Egresado\EvaluacionController;
use App\Http\Controllers\Api\V1\Egresado\PostulacionController;
use App\Http\Controllers\Api\V1\Evaluador\InformePostulacionController;
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
    Route::get('empresa/catalogo-solicitud', [RegistroSolicitudController::class, 'catalogo'])->name('empresa.catalogo');
    Route::post('empresa/registro-solicitud', [RegistroSolicitudController::class, 'store'])->middleware('throttle:registro')->name('empresa.registro');

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

        Route::prefix('egresado')->name('egresado.')->group(function () {
            Route::middleware('rol:EGRESADO')->group(function () {
                Route::get('convocatorias', [PostulacionController::class, 'convocatorias'])->name('convocatorias');
                Route::post('postulaciones', [PostulacionController::class, 'store'])->name('postulaciones.store');
                Route::post('postulaciones/{postulacion}/documentos', [DocumentoController::class, 'store'])->whereNumber('postulacion')->name('documentos.store');
                Route::get('documentos/{documento}/archivo', [DocumentoController::class, 'archivo'])->whereNumber('documento')->name('documentos.archivo');
            });

            Route::middleware('rol:EGRESADO,CANDIDATO_EXTERNO')->group(function () {
                Route::get('postulaciones', [PostulacionController::class, 'index'])->name('postulaciones.index');
                Route::get('postulaciones/{postulacion}', [PostulacionController::class, 'show'])->whereNumber('postulacion')->name('postulaciones.show');
                Route::get('postulaciones/{postulacion}/pruebas', [EvaluacionController::class, 'modulos'])
                    ->whereNumber('postulacion')
                    ->name('pruebas.modulos');
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
        });

        Route::prefix('evaluador')->name('evaluador.')->group(function () {
            Route::middleware('permiso:postulaciones.ver')->group(function () {
                Route::get('postulaciones', [PostulacionEvaluadorController::class, 'index'])
                    ->name('postulaciones.index');
                Route::get('postulaciones/{postulacion}', [PostulacionController::class, 'show'])
                    ->whereNumber('postulacion')
                    ->name('postulaciones.show-evaluador');
                Route::get('documentos/{documento}/archivo', [DocumentoController::class, 'archivo'])
                    ->whereNumber('documento')
                    ->name('documentos.archivo-evaluador');
                Route::get('postulaciones/{postulacion}/resultado-final', [SeleccionController::class, 'resultadoPostulante'])
                    ->whereNumber('postulacion')
                    ->name('postulaciones.resultado-final-evaluador');
                Route::get('postulaciones/{postulacion}/informe', [InformePostulacionController::class, 'show'])
                    ->whereNumber('postulacion')
                    ->name('postulaciones.informe');
            });

            Route::post('postulaciones/{postulacion}/aprobar-requisitos', [RevisionRequisitosController::class, 'aprobar'])
                ->middleware('permiso:requisitos.aprobar')
                ->whereNumber('postulacion')
                ->name('postulaciones.aprobar-requisitos');

            Route::middleware('permiso:cv.calificar')->group(function () {
                Route::get('postulaciones/{postulacion}/analisis-cv', [RevisionCvController::class, 'formulario'])
                    ->whereNumber('postulacion')
                    ->name('postulaciones.analisis-cv');
                Route::post('postulaciones/{postulacion}/calificar-cv', [RevisionCvController::class, 'calificar'])
                    ->whereNumber('postulacion')
                    ->name('postulaciones.calificar-cv');
            });

            Route::middleware('permiso:entrevista.calificar')->group(function () {
                Route::get('postulaciones/{postulacion}/entrevista', [RevisionEntrevistaController::class, 'formulario'])
                    ->whereNumber('postulacion')
                    ->name('postulaciones.entrevista');
                Route::post('postulaciones/{postulacion}/calificar-entrevista', [RevisionEntrevistaController::class, 'calificar'])
                    ->whereNumber('postulacion')
                    ->name('postulaciones.calificar-entrevista');
            });

            Route::middleware('permiso:ranking.adjudicar')->group(function () {
                Route::get('procesos/{proceso}/ranking', [SeleccionController::class, 'ranking'])
                    ->whereNumber('proceso')
                    ->name('procesos.ranking');
                Route::post('procesos/{proceso}/adjudicar', [SeleccionController::class, 'adjudicar'])
                    ->whereNumber('proceso')
                    ->name('procesos.adjudicar');
            });
        });

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::middleware('permiso:facultades.gestionar')->group(function () {
                Route::apiResource('facultades', FacultadController::class)
                    ->parameters(['facultades' => 'facultad'])
                    ->whereNumber('facultad');
            });

            Route::middleware('permiso:carreras.gestionar')->group(function () {
                Route::apiResource('carreras', CarreraController::class)
                    ->parameters(['carreras' => 'carrera'])
                    ->whereNumber('carrera');
            });

            Route::middleware('permiso:convocatorias.gestionar')->group(function () {
                Route::apiResource('convocatorias', ConvocatoriaAdminController::class)
                    ->parameters(['convocatorias' => 'proceso'])
                    ->whereNumber('proceso');
                Route::get('procesos/{proceso}/perfil-ideal', [PerfilIdealController::class, 'show'])->whereNumber('proceso')->name('procesos.perfil-ideal.show');
                Route::put('procesos/{proceso}/perfil-ideal', [PerfilIdealController::class, 'update'])->whereNumber('proceso')->name('procesos.perfil-ideal.update');
            });

            Route::middleware('permiso:preguntas.gestionar')->group(function () {
                Route::get('banco-preguntas/catalogo', [PreguntaAdminController::class, 'catalogoPruebas'])->name('banco-preguntas.catalogo');
                Route::get('banco-preguntas/jerarquia', [PreguntaAdminController::class, 'jerarquia'])->name('banco-preguntas.jerarquia');
                Route::patch('familias/{familia}/tiempo', [PreguntaAdminController::class, 'actualizarTiempoFamilia'])->name('familias.tiempo');
                Route::post('pruebas', [PreguntaAdminController::class, 'storePrueba'])->name('pruebas.store');
                Route::patch('pruebas/{prueba}', [PreguntaAdminController::class, 'actualizarPrueba'])
                    ->whereNumber('prueba')
                    ->name('pruebas.update');
                Route::put('banco-preguntas/pruebas/{prueba}', [PreguntaAdminController::class, 'actualizarConfiguracionPrueba'])
                    ->whereNumber('prueba')
                    ->name('banco-preguntas.pruebas.update');
                Route::post('preguntas/imagen', [PreguntaAdminController::class, 'subirImagen'])->name('preguntas.imagen');
                Route::apiResource('preguntas', PreguntaAdminController::class)
                    ->parameters(['preguntas' => 'pregunta'])
                    ->whereNumber('pregunta');
            });

            Route::get('usuarios', [UsuarioAdminController::class, 'index'])
                ->middleware('permiso:usuarios.ver,usuarios.gestionar')
                ->name('usuarios.index');
            Route::post('usuarios', [UsuarioAdminController::class, 'store'])
                ->middleware('permiso:usuarios.gestionar')
                ->name('usuarios.store');
            Route::put('usuarios/{usuario}', [UsuarioAdminController::class, 'update'])
                ->middleware('permiso:usuarios.gestionar')
                ->whereNumber('usuario')
                ->name('usuarios.update');

            Route::middleware('permiso:permisos.gestionar')->group(function () {
                Route::get('roles-permisos', [UsuarioAdminController::class, 'rolesPermisos'])->name('roles-permisos');
                Route::post('roles', [UsuarioAdminController::class, 'storeRol'])->name('roles.store');
                Route::put('roles/{rol}/permisos', [UsuarioAdminController::class, 'actualizarPermisosRol'])->whereNumber('rol')->name('roles.permisos.update');
            });

            Route::middleware('permiso:solicitudes.gestionar')->group(function () {
                Route::get('solicitudes-empresa', [SolicitudEmpresaController::class, 'index'])->name('solicitudes-empresa.index');
                Route::get('solicitudes-empresa/{proceso}', [SolicitudEmpresaController::class, 'show'])->whereNumber('proceso')->name('solicitudes-empresa.show');
                Route::post('solicitudes-empresa/{proceso}/aprobar', [SolicitudEmpresaController::class, 'aprobar'])->whereNumber('proceso')->name('solicitudes-empresa.aprobar');
            });
        });

        Route::prefix('empresa')->middleware('rol:EMPRESA')->name('empresa.')->group(function () {
            Route::get('solicitudes', [PanelEmpresaController::class, 'index'])->name('solicitudes.index');
            Route::get('postulaciones/{postulacion}/informe', [PanelEmpresaController::class, 'informe'])
                ->whereNumber('postulacion')
                ->name('postulaciones.informe');
        });
    });
});
