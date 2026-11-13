<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('talento:generar-estudiantes-prueba', function () {
    require_once database_path('seeders/EstudiantesPruebaSeeder.php');
    $seeder = new \Database\Seeders\EstudiantesPruebaSeeder();
    $seeder->setCommand($this);
    $seeder->run();
    $this->info("¡Estudiantes de prueba generados exitosamente con notas altas hasta la etapa de CV!");
});

Artisan::command('talento:sembrar-permisos', function () {
    require_once database_path('seeders/PermisoSeeder.php');
    $seeder = new \Database\Seeders\PermisoSeeder();
    $seeder->setCommand($this);
    $seeder->run();
    $this->info("¡Catálogo de permisos y asignación de roles (RBAC) sembrado exitosamente!");
});

Artisan::command('talento:sembrar-uagrm', function () {
    require_once database_path('seeders/UagrmFacultadesCarrerasSeeder.php');
    $seeder = new \Database\Seeders\UagrmFacultadesCarrerasSeeder();
    $seeder->setCommand($this);
    $seeder->run();
    $this->info("¡Facultades y carreras oficiales de la UAGRM registradas exitosamente!");
});

Artisan::command('talento:aprobar-requisitos {id=1}', function (int $id, \App\Actions\Evaluacion\AprobarRequisitosPostulacion $aprobar) {
    $admin = \App\Models\Usuario::whereHas('rol', fn ($q) => $q->where('codigo', \App\Enums\CodigoRol::Admin->value))->firstOrFail();
    $postulacion = \App\Models\Postulacion::findOrFail($id);

    $this->info("Aprobando requisitos para la postulación #{$postulacion->id} ({$postulacion->codigo}) como {$admin->correo}...");
    $aprobar->handle($admin, $postulacion);

    $this->info("¡Requisitos aprobados exitosamente!");
    $etapas = $postulacion->etapas()->with('procesoEtapa.etapa')->get()->sortBy(fn ($e) => $e->procesoEtapa->orden);
    foreach ($etapas as $e) {
        $this->line("  Etapa {$e->procesoEtapa->orden}: {$e->procesoEtapa->etapa->nombre} -> {$e->estado->value}");
    }
});

Artisan::command('talento:sincronizar-etapas', function () {
    $etapasCatalogo = \App\Models\Etapa::query()->pluck('id', 'codigo');
    $pruebasCatalogo = \App\Models\Prueba::query()->pluck('id', 'codigo');



    $etapasFlujoA = [
        'INSCRIPCION' => ['prueba' => null, 'peso' => '0'],
        'EVALUACION_TECNICA' => ['prueba' => \App\Models\Prueba::PPA, 'peso' => '20'],
        'REQUISITOS' => ['prueba' => null, 'peso' => '0'],
        'EVALUACION_PSICOLOGICA' => ['prueba' => 'TEST_PERSONALIDAD', 'peso' => '25'],
        'HABILIDADES_BLANDAS' => ['prueba' => 'TEST_HABILIDADES_BLANDAS', 'peso' => '20'],
        'ANALISIS_CV' => ['prueba' => 'CV_ANALISIS', 'peso' => '15'],
        'ENTREVISTA' => ['prueba' => 'ENTREVISTA', 'peso' => '20'],
        'SELECCION' => ['prueba' => null, 'peso' => '0'],
    ];

    \Illuminate\Support\Facades\DB::transaction(function () use ($etapasCatalogo, $pruebasCatalogo, $etapasFlujoA) {
        $procesos = \App\Models\Proceso::all();

        foreach ($procesos as $proceso) {
            $this->info("Sincronizando Proceso #{$proceso->id}: {$proceso->nombre}");

            // 1. Desplazar órdenes temporalmente para evitar choques con uq_proceso_etapa_orden
            \Illuminate\Support\Facades\DB::table('talento.proceso_etapa')
                ->where('proceso_id', $proceso->id)
                ->update(['orden' => \Illuminate\Support\Facades\DB::raw('orden + 100')]);

            $orden = 1;
            foreach ($etapasFlujoA as $codigoEtapa => $conf) {
                $etapaId = $etapasCatalogo[$codigoEtapa];
                $procesoEtapa = \App\Models\ProcesoEtapa::query()
                    ->where('proceso_id', $proceso->id)
                    ->where('etapa_id', $etapaId)
                    ->first();

                if (! $procesoEtapa) {
                    $procesoEtapa = \App\Models\ProcesoEtapa::create([
                        'proceso_id' => $proceso->id,
                        'etapa_id' => $etapaId,
                        'orden' => $orden++,
                        'activa' => true,
                    ]);
                } else {
                    $procesoEtapa->update(['orden' => $orden++]);
                }

                // Ajustar prueba y peso
                if ($conf['prueba'] !== null) {
                    $pruebaId = $pruebasCatalogo[$conf['prueba']];
                    \App\Models\ProcesoPrueba::query()->updateOrCreate(
                        ['proceso_etapa_id' => $procesoEtapa->id, 'prueba_id' => $pruebaId],
                        [
                            'peso' => $conf['peso'],
                            'umbral_origen' => $conf['prueba'] === \App\Models\Prueba::PPA ? \App\Enums\UmbralOrigen::Facultad : \App\Enums\UmbralOrigen::Fijo,
                            'max_intentos' => 1,
                        ]
                    );
                }
            }

            // 2. Sincronizar postulaciones del proceso
            $proceso->refresh();
            $etapasOrdenadas = $proceso->etapas()->with('etapa')->orderBy('orden')->get();

            foreach ($proceso->postulaciones as $postulacion) {
                $this->info("  Sincronizando Postulación #{$postulacion->id}");

                foreach ($etapasOrdenadas as $pe) {
                    $postEtapa = \App\Models\PostulacionEtapa::query()
                        ->where('postulacion_id', $postulacion->id)
                        ->where('proceso_etapa_id', $pe->id)
                        ->first();

                    // Determinar estado según nuevo orden:
                    // orden 1 (INSCRIPCION): APROBADA
                    // orden 2 (EVALUACION_TECNICA): APROBADA (PPA superado)
                    // orden 3 (REQUISITOS): PENDIENTE (si aún no se aprobaron documentos)
                    // orden >= 4: BLOQUEADA
                    $nuevoEstado = match ($pe->orden) {
                        1 => \App\Enums\EstadoEtapa::Aprobada,
                        2 => \App\Enums\EstadoEtapa::Aprobada,
                        3 => \App\Enums\EstadoEtapa::Pendiente,
                        default => \App\Enums\EstadoEtapa::Bloqueada,
                    };

                    if (! $postEtapa) {
                        \App\Models\PostulacionEtapa::create([
                            'postulacion_id' => $postulacion->id,
                            'proceso_etapa_id' => $pe->id,
                            'estado' => $nuevoEstado,
                            'iniciada_en' => in_array($nuevoEstado, [\App\Enums\EstadoEtapa::Aprobada, \App\Enums\EstadoEtapa::Pendiente]) ? now() : null,
                            'finalizada_en' => $nuevoEstado === \App\Enums\EstadoEtapa::Aprobada ? now() : null,
                        ]);
                    } else {
                        $postEtapa->update([
                            'estado' => $nuevoEstado,
                            'iniciada_en' => in_array($nuevoEstado, [\App\Enums\EstadoEtapa::Aprobada, \App\Enums\EstadoEtapa::Pendiente]) ? now() : null,
                            'finalizada_en' => $nuevoEstado === \App\Enums\EstadoEtapa::Aprobada ? now() : null,
                        ]);
                    }
                }
            }
        }
    });

    $this->info("¡Sincronización completada con éxito!");
});

Artisan::command('talento:completar-etapa-psicologica {id=1}', function (int $id, \App\Actions\Evaluacion\IniciarIntentoPrueba $iniciar, \App\Actions\Evaluacion\GuardarRespuestaIntento $guardar, \App\Actions\Evaluacion\FinalizarIntentoPrueba $finalizar) {
    $postulacion = \App\Models\Postulacion::with(['persona.usuario', 'proceso'])->findOrFail($id);
    $usuario = $postulacion->persona->usuario;

    $this->info("Simulando y aprobando las 6 pruebas de Evaluación Psicológica para la postulación #{$postulacion->id}...");

    $etapaPsico = $postulacion->etapas()
        ->whereHas('procesoEtapa.etapa', fn ($q) => $q->where('codigo', 'EVALUACION_PSICOLOGICA'))
        ->firstOrFail();

    $procesosPruebas = \App\Models\ProcesoPrueba::where('proceso_etapa_id', $etapaPsico->proceso_etapa_id)
        ->with('prueba')
        ->orderBy('orden')
        ->get();

    foreach ($procesosPruebas as $pp) {
        $codigo = $pp->prueba->codigo;
        $this->line("Procesando prueba: [{$codigo}] {$pp->prueba->nombre}...");

        // Verificar si ya existe intento aprobado
        $resultado = \App\Models\ResultadoPrueba::where('postulacion_id', $postulacion->id)
            ->where('proceso_prueba_id', $pp->id)
            ->first();

        if ($resultado && $resultado->aprobada) {
            $this->info("  -> Ya aprobada con {$resultado->puntaje_bruto}/{$resultado->puntaje_maximo} ({$resultado->puntaje_normalizado}%).");
            continue;
        }

        // Si existe intento previo no aprobado, buscarlo o iniciar uno nuevo
        $intento = \App\Models\IntentoPrueba::where('postulacion_id', $postulacion->id)
            ->where('proceso_prueba_id', $pp->id)
            ->first();

        if (! $intento || $intento->estado === \App\Enums\EstadoIntento::Finalizado) {
            if ($intento) {
                // Borrar intento y resultado previo fallido para reiniciar limpio
                \App\Models\RespuestaIntento::where('intento_id', $intento->id)->delete();
                $resultado?->delete();
                $intento->delete();
            }
            $intento = $iniciar->handle($usuario, $postulacion, $pp);
        }

        $preguntas = \App\Models\Pregunta::where('prueba_id', $pp->prueba_id)
            ->with('opciones')
            ->orderBy('orden')
            ->get();

        $i = 0;
        foreach ($preguntas as $p) {
            // Seleccionar opción correcta o con mayor puntaje para garantizar aprobación
            $opcion = $p->opciones->sortByDesc('puntaje')->first();
            if ($opcion) {
                $guardar->handle($usuario, $intento, $p->id, $opcion->id);
            }
            $i++;
        }

        $res = $finalizar->handle($usuario, $intento);
        $this->info("  -> Aprobada: {$res->puntaje_bruto}/{$res->puntaje_maximo} ({$res->puntaje_normalizado}%).");
    }

    $postulacion->refresh();
    $this->info("\n=== ESTADO ACTUAL DEL EMBUDO DE SELECCIÓN ===");
    $etapas = $postulacion->etapas()->with('procesoEtapa.etapa')->get()->sortBy(fn ($e) => $e->procesoEtapa->orden);
    foreach ($etapas as $e) {
        $simbolo = match ($e->estado) {
            \App\Enums\EstadoEtapa::Aprobada => '✓ APROBADA',
            \App\Enums\EstadoEtapa::Pendiente => '▶ PENDIENTE (ACTIVA)',
            default => '🔒 BLOQUEADA',
        };
        $this->line("  Etapa {$e->procesoEtapa->orden}: {$e->procesoEtapa->etapa->nombre} -> {$simbolo}");
    }
});

Artisan::command('talento:probar-habilidades-blandas {id=1}', function (int $id, \App\Actions\Evaluacion\IniciarIntentoPrueba $iniciar, \App\Actions\Evaluacion\GuardarRespuestaIntento $guardar, \App\Actions\Evaluacion\FinalizarIntentoPrueba $finalizar) {
    $postulacion = \App\Models\Postulacion::with(['persona.usuario', 'proceso'])->findOrFail($id);
    $usuario = $postulacion->persona->usuario;

    $procesoPrueba = \App\Models\ProcesoPrueba::whereHas('procesoEtapa', fn ($q) => $q->where('proceso_id', $postulacion->proceso_id))
        ->whereHas('prueba', fn ($q) => $q->where('codigo', 'TEST_HABILIDADES_BLANDAS'))
        ->firstOrFail();

    $this->info("Iniciando prueba [{$procesoPrueba->prueba->nombre}] para la postulación #{$postulacion->id}...");

    // Asegurar que la etapa de habilidades blandas esté en PENDIENTE para la simulación
    $etapaBlandas = $postulacion->etapas()
        ->where('proceso_etapa_id', $procesoPrueba->proceso_etapa_id)
        ->first();
    if ($etapaBlandas) {
        $etapaBlandas->update(['estado' => \App\Enums\EstadoEtapa::Pendiente]);
    }

    $intento = \App\Models\IntentoPrueba::where('postulacion_id', $postulacion->id)
        ->where('proceso_prueba_id', $procesoPrueba->id)
        ->first();

    if ($intento) {
        \App\Models\RespuestaIntento::where('intento_id', $intento->id)->delete();
        \App\Models\ResultadoPrueba::where('intento_id', $intento->id)->delete();
        $intento->delete();
    }

    $intento = $iniciar->handle($usuario, $postulacion, $procesoPrueba);
    $this->info("Intento #{$intento->nro_intento} iniciado (ID: {$intento->id}).");

    $preguntas = \App\Models\Pregunta::where('prueba_id', $procesoPrueba->prueba_id)
        ->with('opciones')
        ->orderBy('orden')
        ->get();

    $this->info("Simulando respuestas Likert (4 y 5) a {$preguntas->count()} preguntas de habilidades blandas...");

    foreach ($preguntas as $p) {
        // Marcamos puntuaciones altas (4 o 5) para un perfil sobresaliente / avanzado
        $opcion = $p->opciones->firstWhere('etiqueta', rand(0, 1) === 1 ? '5' : '4');
        if ($opcion) {
            $guardar->handle($usuario, $intento, $p->id, $opcion->id);
        }
    }

    $resultado = $finalizar->handle($usuario, $intento);

    // Consultar evaluacionActiva
    $request = \Illuminate\Http\Request::create('/api/v1/egresado/postulaciones/' . $postulacion->id . '/evaluacion-activa', 'GET', [
        'proceso_prueba_id' => $procesoPrueba->id,
    ]);
    $request->setUserResolver(fn () => $usuario);
    $controller = app(\App\Http\Controllers\Api\V1\Egresado\EvaluacionController::class);
    $response = $controller->evaluacionActiva($request, $postulacion->id);
    $data = $response->getData(true)['data'];

    $this->info("¡Evaluación de Habilidades Blandas finalizada y calificada!");
    $this->line("  Puntaje bruto: {$resultado->puntaje_bruto} / {$resultado->puntaje_maximo}");
    $this->line("  Porcentaje normalizado: {$resultado->puntaje_normalizado}%");
    $this->line("  Aprobada: " . ($resultado->aprobada ? 'SÍ' : 'NO'));

    $diag = $data['resultado']['diagnostico_habilidades_blandas'] ?? null;
    if ($diag) {
        $this->line("  --- Diagnóstico de Habilidades Blandas ---");
        $this->line("  Nivel Global: {$diag['nivel_global']}");
        $this->line("  Puntaje: {$diag['puntaje_total']}/{$diag['puntaje_maximo']} ({$diag['porcentaje_global']}%)");
        foreach ($diag['dimensiones'] as $d) {
            $this->line("    • {$d['nombre']}: {$d['puntaje']}/{$d['puntaje_maximo']} ({$d['porcentaje']}%) -> {$d['nivel']}");
        }
    }
});

Artisan::command('talento:calificar-entrevista {id=1}', function (int $id, \App\Actions\Evaluacion\CalificarEntrevista $calificar) {
    $postulacion = \App\Models\Postulacion::with(['persona', 'proceso'])->findOrFail($id);
    $admin = \App\Models\Usuario::where('correo', 'admin@talento.test')->firstOrFail();

    $etapaEntrevista = $postulacion->etapas()
        ->whereHas('procesoEtapa.etapa', fn ($q) => $q->where('codigo', 'ENTREVISTA'))
        ->firstOrFail();

    $procesoPrueba = \App\Models\ProcesoPrueba::where('proceso_etapa_id', $etapaEntrevista->proceso_etapa_id)
        ->whereHas('prueba', fn ($q) => $q->where('codigo', 'ENTREVISTA'))
        ->firstOrFail();

    $criterios = \App\Models\RubricaCriterio::where('prueba_id', $procesoPrueba->prueba_id)->orderBy('orden')->get();

    $this->info("Calificando Entrevista Personal para Postulación #{$postulacion->id}...");

    $calificaciones = [];
    $puntajesEjemplo = [9.5, 9.0, 10.0, 9.0];
    foreach ($criterios as $idx => $crit) {
        $calificaciones[] = [
            'criterio_id' => $crit->id,
            'puntaje' => $puntajesEjemplo[$idx % count($puntajesEjemplo)],
            'comentario' => "Excelente desempeño evaluado en {$crit->nombre}.",
        ];
    }

    $resultado = $calificar->handle($admin, $postulacion, [
        'calificaciones' => $calificaciones,
        'observaciones' => 'Candidato con gran capacidad técnica, oratoria impecable y alto compromiso institucional.',
    ]);

    $this->info("¡Entrevista calificada exitosamente!");
    $this->line("  Puntaje: {$resultado->puntaje_bruto}/{$resultado->puntaje_maximo} ({$resultado->puntaje_normalizado}%)");
    $this->line("  Aprobada: " . ($resultado->aprobada ? 'SÍ' : 'NO'));
});

Artisan::command('talento:calcular-ranking {id=1}', function (int $id, \App\Actions\Evaluacion\CalcularRankingSeleccion $calcular) {
    $proceso = \App\Models\Proceso::with(['convocatoria.carrera'])->findOrFail($id);
    $this->info("Calculando ranking para Proceso #{$proceso->id}: {$proceso->nombre} ({$proceso->convocatoria->carrera->nombre})...");

    $ranking = $calcular->handle($proceso);

    $this->info("=== CUADRO DE HONOR Y RANKING FINAL ===");
    foreach ($ranking as $cand) {
        $this->line(sprintf("  #%d | Postulación #%d (%s) | %s | Puntaje: %6.2f pts | Top: %s",
            $cand['posicion'], $cand['postulacion_id'], $cand['codigo_postulacion'], $cand['postulante']['nombre_completo'], $cand['puntaje_total'], $cand['es_top'] ? 'SÍ (Terna/Quinteto)' : 'NO'
        ));
        $this->line("      Desglose ponderado:");
        foreach ($cand['desglose'] as $d) {
            $this->line(sprintf("        • %-30s | Peso: %2d%% | Norm: %6.2f%% | Aporte: %5.2f pts",
                $d['nombre'], (int)$d['peso'], (float)$d['puntaje_normalizado'], (float)$d['aporte_ponderado']
            ));
        }
    }
});

Artisan::command('talento:adjudicar-seleccion {id=1}', function (int $id, \App\Actions\Evaluacion\AdjudicarSeleccion $adjudicar) {
    $proceso = \App\Models\Proceso::with(['convocatoria.carrera'])->findOrFail($id);
    $admin = \App\Models\Usuario::where('correo', 'admin@talento.test')->firstOrFail();

    $this->info("Adjudicando plazas para Proceso #{$proceso->id}...");
    $resultado = $adjudicar->handle($admin, $proceso);

    $this->info("¡Selección adjudicada y publicada exitosamente por {$resultado['publicado_por']}!");
    $this->line("  Adjudicados: {$resultado['adjudicados_count']}");
    $this->line("  No adjudicados: {$resultado['no_adjudicados_count']}");
    foreach ($resultado['ranking'] as $r) {
        $this->line(sprintf("  #%d | Postulación #%d (%s) | %s | %6.2f pts | Adjudicado: %s",
            $r['posicion'], $r['postulacion_id'], $r['codigo'], $r['postulante'], (float)$r['puntaje_total'], $r['adjudicado'] ? '🏆 SÍ' : 'NO'
        ));
    }
});

Artisan::command('talento:ver-resumen {id=1}', function (int $id) {
    $this->info("Total postulaciones en el sistema: " . \App\Models\Postulacion::count());
    $postulacion = \App\Models\Postulacion::with(['persona.egresados', 'persona.usuario', 'proceso.convocatoria.carrera.facultad', 'etapas.procesoEtapa.etapa'])->findOrFail($id);
    $this->info("=== RESUMEN POSTULACIÓN #{$postulacion->id} ({$postulacion->codigo}) ===");
    $this->line("Candidato: {$postulacion->persona->nombre_completo} (CI: {$postulacion->persona->ci})");
    $egresado = $postulacion->persona->egresados->first();
    $this->line("PPA Egresado: " . ($egresado?->ppa ?? 'Sin PPA'));
    $this->line("PPA Mínimo Facultad: " . ($postulacion->proceso->convocatoria->carrera->facultad->ppa_minimo ?? 'N/A'));
    $this->line("Carrera: {$postulacion->proceso->convocatoria->carrera->nombre}");
    $this->info("--- ETAPAS ---");
    foreach ($postulacion->etapas->sortBy('procesoEtapa.orden') as $pe) {
        $this->line(sprintf("  [%d] %-25s: %s", $pe->procesoEtapa->orden, $pe->procesoEtapa->etapa->nombre, $pe->estado->value));
    }
    $this->info("--- DOCUMENTOS SUBIDOS ---");
    $docs = $postulacion->documentos()->where('es_vigente', true)->with('tipoDocumento')->get();
    if ($docs->isEmpty()) {
        $this->line("  (Sin documentos vigentes subidos)");
    } else {
        foreach ($docs as $d) {
            $this->line(sprintf("  • %-25s | Estado: %s | Archivo: %s", $d->tipoDocumento?->nombre ?? 'N/A', $d->estado?->value, $d->nombre_original ?? 'archivo'));
        }
    }
    $this->info("--- PRUEBAS CONFIGURADAS EN EL PROCESO ---");
    $procesoEtapas = $postulacion->proceso->etapas()->with(['etapa', 'pruebas.prueba'])->orderBy('orden')->get();
    foreach ($procesoEtapas as $pe) {
        $this->line("  [Etapa {$pe->orden}: {$pe->etapa->nombre}]");
        foreach ($pe->pruebas as $pp) {
            $this->line(sprintf("    -> Prueba: %-30s | Codigo: %-25s | Peso: %2d%% | Min: %s", 
                $pp->prueba->nombre, $pp->prueba->codigo, (int)$pp->peso, $pp->puntaje_minimo ?? 'N/A'));
        }
    }
    $this->info("--- PRUEBAS Y CALIFICACIONES REGISTRADAS ---");
    $resultados = \App\Models\ResultadoPrueba::where('postulacion_id', $postulacion->id)->with('procesoPrueba.prueba')->get();
    foreach ($resultados as $r) {
        $prueba = $r->procesoPrueba?->prueba;
        $peso = $r->procesoPrueba?->peso ?? 0;
        $this->line(sprintf("  • %-30s | Bruto: %6.2f/%-6.2f | Norm: %6.2f%% | Peso: %2d%% | Aprobada: %s", 
            $prueba?->nombre ?? 'N/A', (float)$r->puntaje_bruto, (float)$r->puntaje_maximo, (float)$r->puntaje_normalizado, (int)$peso, $r->aprobada ? 'SÍ' : 'NO'));
    }
});

