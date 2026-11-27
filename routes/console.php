<?php

use App\Actions\Evaluacion\AdjudicarSeleccion;
use App\Actions\Evaluacion\AprobarRequisitosPostulacion;
use App\Actions\Evaluacion\AsignarInstrumentosPostulacion;
use App\Actions\Evaluacion\AvanzarEtapaTrasResultados;
use App\Actions\Evaluacion\CalcularRankingSeleccion;
use App\Actions\Evaluacion\CalificarEntrevista;
use App\Actions\Evaluacion\FinalizarIntentoPrueba;
use App\Actions\Evaluacion\GuardarRespuestaIntento;
use App\Actions\Evaluacion\IniciarIntentoPrueba;
use App\Enums\CodigoRol;
use App\Enums\EstadoEtapa;
use App\Enums\EstadoIntento;
use App\Enums\EstadoPostulacion;
use App\Enums\UmbralOrigen;
use App\Http\Controllers\Api\V1\Egresado\EvaluacionController;
use App\Models\AsignacionPrueba;
use App\Models\Etapa;
use App\Models\IntentoPrueba;
use App\Models\Postulacion;
use App\Models\PostulacionEtapa;
use App\Models\Pregunta;
use App\Models\Proceso;
use App\Models\ProcesoEtapa;
use App\Models\ProcesoPrueba;
use App\Models\Prueba;
use App\Models\RespuestaIntento;
use App\Models\ResultadoPrueba;
use App\Models\RubricaCriterio;
use App\Models\Usuario;
use Database\Seeders\CandidatosCompletosSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\EmbudoEvaluacionPanelSeeder;
use Database\Seeders\EstudiantesPruebaSeeder;
use Database\Seeders\UsuariosPanelSeeder;
use Database\Seeders\PermisoSeeder;
use Database\Seeders\UagrmFacultadesCarrerasSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('talento:generar-estudiantes-prueba', function () {
    require_once database_path('seeders/EstudiantesPruebaSeeder.php');
    $seeder = new EstudiantesPruebaSeeder;
    $seeder->setCommand($this);
    $seeder->run();
    $this->info('¡Estudiantes de prueba generados exitosamente con notas altas hasta la etapa de CV!');
});

Artisan::command('talento:generar-candidatos-completos', function () {
    require_once database_path('seeders/CandidatosCompletosSeeder.php');
    $seeder = new CandidatosCompletosSeeder;
    $seeder->setCommand($this);
    $seeder->run();
    $this->info('¡Egresados con proceso completo generados (completo.*@talento.test / password123)!');
});

Artisan::command('talento:sembrar-permisos', function () {
    require_once database_path('seeders/PermisoSeeder.php');
    $seeder = new PermisoSeeder;
    $seeder->setCommand($this);
    $seeder->run();
    $this->info('¡Catálogo de permisos y asignación de roles (RBAC) sembrado exitosamente!');
});

Artisan::command('talento:sembrar-uagrm', function () {
    require_once database_path('seeders/UagrmFacultadesCarrerasSeeder.php');
    $seeder = new UagrmFacultadesCarrerasSeeder;
    $seeder->setCommand($this);
    $seeder->run();
    $this->info('¡Facultades y carreras oficiales de la UAGRM registradas exitosamente!');
});

Artisan::command('talento:aprobar-requisitos {id=1}', function (int $id, AprobarRequisitosPostulacion $aprobar) {
    $admin = Usuario::whereHas('rol', fn ($q) => $q->where('codigo', CodigoRol::Admin->value))->firstOrFail();
    $postulacion = Postulacion::findOrFail($id);

    $this->info("Aprobando requisitos para la postulación #{$postulacion->id} ({$postulacion->codigo}) como {$admin->correo}...");
    $aprobar->handle($admin, $postulacion);

    $this->info('¡Requisitos aprobados exitosamente!');
    $etapas = $postulacion->etapas()->with('procesoEtapa.etapa')->get()->sortBy(fn ($e) => $e->procesoEtapa->orden);
    foreach ($etapas as $e) {
        $this->line("  Etapa {$e->procesoEtapa->orden}: {$e->procesoEtapa->etapa->nombre} -> {$e->estado->value}");
    }
});

Artisan::command('talento:sincronizar-etapas', function () {
    $etapasCatalogo = Etapa::query()->pluck('id', 'codigo');
    $pruebasCatalogo = Prueba::query()->pluck('id', 'codigo');

    $etapasFlujoA = [
        'INSCRIPCION' => ['prueba' => null, 'peso' => '0'],
        'EVALUACION_TECNICA' => ['prueba' => Prueba::PPA, 'peso' => '20'],
        'REQUISITOS' => ['prueba' => null, 'peso' => '0'],
        'EVALUACION_PSICOLOGICA' => ['prueba' => '16PF_FORMA_A', 'peso' => '10'],
        'ANALISIS_CV' => ['prueba' => 'CV_ANALISIS', 'peso' => '15'],
        'ENTREVISTA' => ['prueba' => 'ENTREVISTA', 'peso' => '20'],
        'SELECCION' => ['prueba' => null, 'peso' => '0'],
    ];

    DB::transaction(function () use ($etapasCatalogo, $pruebasCatalogo, $etapasFlujoA) {
        $procesos = Proceso::all();

        foreach ($procesos as $proceso) {
            $this->info("Sincronizando Proceso #{$proceso->id}: {$proceso->nombre}");

            // 1. Desplazar órdenes temporalmente para evitar choques con uq_proceso_etapa_orden
            DB::table('talento.proceso_etapa')
                ->where('proceso_id', $proceso->id)
                ->update(['orden' => DB::raw('orden + 100')]);

            $orden = 1;
            foreach ($etapasFlujoA as $codigoEtapa => $conf) {
                $etapaId = $etapasCatalogo[$codigoEtapa];
                $procesoEtapa = ProcesoEtapa::query()
                    ->where('proceso_id', $proceso->id)
                    ->where('etapa_id', $etapaId)
                    ->first();

                if (! $procesoEtapa) {
                    $procesoEtapa = ProcesoEtapa::create([
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
                    ProcesoPrueba::query()->updateOrCreate(
                        ['proceso_etapa_id' => $procesoEtapa->id, 'prueba_id' => $pruebaId],
                        [
                            'peso' => $conf['peso'],
                            'umbral_origen' => $conf['prueba'] === Prueba::PPA ? UmbralOrigen::Facultad : UmbralOrigen::Fijo,
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
                    $postEtapa = PostulacionEtapa::query()
                        ->where('postulacion_id', $postulacion->id)
                        ->where('proceso_etapa_id', $pe->id)
                        ->first();

                    // Determinar estado según nuevo orden:
                    // orden 1 (INSCRIPCION): APROBADA
                    // orden 2 (EVALUACION_TECNICA): APROBADA (PPA superado)
                    // orden 3 (REQUISITOS): PENDIENTE (si aún no se aprobaron documentos)
                    // orden >= 4: BLOQUEADA
                    $nuevoEstado = match ($pe->orden) {
                        1 => EstadoEtapa::Aprobada,
                        2 => EstadoEtapa::Aprobada,
                        3 => EstadoEtapa::Pendiente,
                        default => EstadoEtapa::Bloqueada,
                    };

                    if (! $postEtapa) {
                        PostulacionEtapa::create([
                            'postulacion_id' => $postulacion->id,
                            'proceso_etapa_id' => $pe->id,
                            'estado' => $nuevoEstado,
                            'iniciada_en' => in_array($nuevoEstado, [EstadoEtapa::Aprobada, EstadoEtapa::Pendiente]) ? now() : null,
                            'finalizada_en' => $nuevoEstado === EstadoEtapa::Aprobada ? now() : null,
                        ]);
                    } else {
                        $postEtapa->update([
                            'estado' => $nuevoEstado,
                            'iniciada_en' => in_array($nuevoEstado, [EstadoEtapa::Aprobada, EstadoEtapa::Pendiente]) ? now() : null,
                            'finalizada_en' => $nuevoEstado === EstadoEtapa::Aprobada ? now() : null,
                        ]);
                    }
                }
            }
        }
    });

    $this->info('¡Sincronización completada con éxito!');
});

Artisan::command('talento:completar-etapa-psicologica {id=1}', function (int $id, IniciarIntentoPrueba $iniciar, GuardarRespuestaIntento $guardar, FinalizarIntentoPrueba $finalizar) {
    $postulacion = Postulacion::with(['persona.usuario', 'proceso'])->findOrFail($id);
    $usuario = $postulacion->persona->usuario;

    $this->info("Simulando y aprobando las 6 pruebas de Evaluación Psicológica para la postulación #{$postulacion->id}...");

    $etapaPsico = $postulacion->etapas()
        ->whereHas('procesoEtapa.etapa', fn ($q) => $q->where('codigo', 'EVALUACION_PSICOLOGICA'))
        ->firstOrFail();

    $etapaPsico->update(['estado' => EstadoEtapa::Pendiente, 'iniciada_en' => now(), 'finalizada_en' => null]);

    app(AsignarInstrumentosPostulacion::class)->handle($postulacion->refresh());

    $procesosPruebas = ProcesoPrueba::where('proceso_etapa_id', $etapaPsico->proceso_etapa_id)
        ->with('prueba')
        ->orderBy('orden')
        ->get();

    foreach ($procesosPruebas as $pp) {
        $codigo = $pp->prueba->codigo;
        $this->line("Procesando prueba: [{$codigo}] {$pp->prueba->nombre}...");

        // Verificar si ya existe intento aprobado
        $resultado = ResultadoPrueba::where('postulacion_id', $postulacion->id)
            ->where('proceso_prueba_id', $pp->id)
            ->first();

        if ($resultado && $resultado->aprobada) {
            $this->info("  -> Ya aprobada con {$resultado->puntaje_bruto}/{$resultado->puntaje_maximo} ({$resultado->puntaje_normalizado}%).");

            continue;
        }

        // Si existe intento previo no aprobado, buscarlo o iniciar uno nuevo
        $intento = IntentoPrueba::where('postulacion_id', $postulacion->id)
            ->where('proceso_prueba_id', $pp->id)
            ->first();

        if (! $intento || $intento->estado === EstadoIntento::Finalizado) {
            if ($intento) {
                // Borrar intento y resultado previo fallido para reiniciar limpio
                RespuestaIntento::where('intento_id', $intento->id)->delete();
                $resultado?->delete();
                $intento->delete();
            }
            $intento = $iniciar->handle($usuario, $postulacion, $pp);
        }

        $preguntas = Pregunta::query()
            ->whereIn('id', $intento->respuestas()->pluck('pregunta_id'))
            ->with('opciones')
            ->orderBy('orden')
            ->get();

        if ($preguntas->isEmpty()) {
            $pruebaId = AsignacionPrueba::query()
                ->where('postulacion_id', $postulacion->id)
                ->where('proceso_prueba_id', $pp->id)
                ->value('prueba_id') ?? $pp->prueba_id;

            $preguntas = Pregunta::where('prueba_id', $pruebaId)
                ->with('opciones')
                ->orderBy('orden')
                ->get();
        }

        if ($codigo === 'TEST_HABILIDADES_BLANDAS' && $preguntas->count() !== 49) {
            $this->warn("  -> El banco de habilidades blandas tiene {$preguntas->count()} ítems (se esperan 49). Ejecute BancoPreguntasHabilidadesBlandasSeeder.");
        }

        foreach ($preguntas as $p) {
            // Seleccionar opción correcta o con mayor puntaje para garantizar aprobación
            $opcion = $p->opciones->sortByDesc('puntaje')->first();
            if ($opcion) {
                $guardar->handle($usuario, $intento, $p->id, $opcion->id);
            }
        }

        $res = $finalizar->handle($usuario, $intento);
        $res->load('dimensiones');
        $dims = $res->dimensiones->count();
        $extra = $codigo === 'TEST_HABILIDADES_BLANDAS' && $dims > 0
            ? ", {$dims} dimensiones"
            : '';
        $this->info("  -> Aprobada: {$res->puntaje_bruto}/{$res->puntaje_maximo} ({$res->puntaje_normalizado}%){$extra}.");
    }

    $postulacion->refresh();
    $this->info("\n=== ESTADO ACTUAL DEL EMBUDO DE SELECCIÓN ===");
    $etapas = $postulacion->etapas()->with('procesoEtapa.etapa')->get()->sortBy(fn ($e) => $e->procesoEtapa->orden);
    foreach ($etapas as $e) {
        $simbolo = match ($e->estado) {
            EstadoEtapa::Aprobada => '✓ APROBADA',
            EstadoEtapa::Pendiente => '▶ PENDIENTE (ACTIVA)',
            default => '🔒 BLOQUEADA',
        };
        $this->line("  Etapa {$e->procesoEtapa->orden}: {$e->procesoEtapa->etapa->nombre} -> {$simbolo}");
    }
});

Artisan::command('talento:probar-habilidades-blandas {id=1}', function (int $id, IniciarIntentoPrueba $iniciar, GuardarRespuestaIntento $guardar, FinalizarIntentoPrueba $finalizar) {
    $postulacion = Postulacion::with(['persona.usuario', 'proceso'])->findOrFail($id);
    $usuario = $postulacion->persona->usuario;

    $procesoPrueba = ProcesoPrueba::query()
        ->whereHas('procesoEtapa', fn ($q) => $q->where('proceso_id', $postulacion->proceso_id))
        ->whereHas('prueba', fn ($q) => $q->where('codigo', 'TEST_HABILIDADES_BLANDAS'))
        ->first();

    if ($procesoPrueba === null) {
        $procesoPrueba = ProcesoPrueba::whereHas('procesoEtapa', fn ($q) => $q->where('proceso_id', $postulacion->proceso_id))
            ->whereHas('prueba', fn ($q) => $q->where('familia', 'HABILIDAD_BLANDA'))
            ->firstOrFail();
    }

    app(AsignarInstrumentosPostulacion::class)->handle($postulacion->refresh());

    $this->info("Iniciando [{$procesoPrueba->prueba->nombre}] para la postulación #{$postulacion->id}…");

    // Asegurar que la etapa de habilidades blandas esté en PENDIENTE para la simulación
    $etapaBlandas = $postulacion->etapas()
        ->where('proceso_etapa_id', $procesoPrueba->proceso_etapa_id)
        ->first();
    if ($etapaBlandas) {
        $etapaBlandas->update(['estado' => EstadoEtapa::Pendiente]);
    }

    $intento = IntentoPrueba::where('postulacion_id', $postulacion->id)
        ->where('proceso_prueba_id', $procesoPrueba->id)
        ->first();

    if ($intento) {
        RespuestaIntento::where('intento_id', $intento->id)->delete();
        ResultadoPrueba::where('intento_id', $intento->id)->delete();
        $intento->delete();
    }

    $intento = $iniciar->handle($usuario, $postulacion, $procesoPrueba);
    $this->info("Intento #{$intento->nro_intento} iniciado (ID: {$intento->id}).");

    $pruebaId = (int) (AsignacionPrueba::query()
        ->where('postulacion_id', $postulacion->id)
        ->where('proceso_prueba_id', $procesoPrueba->id)
        ->value('prueba_id') ?? $procesoPrueba->prueba_id);

    $preguntas = Pregunta::query()
        ->whereIn('id', $intento->respuestas()->pluck('pregunta_id'))
        ->with('opciones')
        ->orderBy('orden')
        ->get();

    if ($preguntas->isEmpty()) {
        $preguntas = Pregunta::where('prueba_id', $pruebaId)
            ->with('opciones')
            ->orderBy('orden')
            ->get();
    }

    $esperadas = 49;
    if ($preguntas->count() !== $esperadas) {
        $this->warn("El banco tiene {$preguntas->count()} ítems; se esperan {$esperadas} (TMMS + competencias). Ejecute: php artisan db:seed --class=Database\\Seeders\\BancoPreguntasHabilidadesBlandasSeeder");
    }

    $this->info("Simulando respuestas Likert (4 y 5) a {$preguntas->count()} preguntas (8 dimensiones)…");

    foreach ($preguntas as $p) {
        // Marcamos puntuaciones altas (4 o 5) para un perfil sobresaliente / avanzado
        $opcion = $p->opciones->firstWhere('etiqueta', rand(0, 1) === 1 ? '5' : '4');
        if ($opcion) {
            $guardar->handle($usuario, $intento, $p->id, $opcion->id);
        }
    }

    $resultado = $finalizar->handle($usuario, $intento);

    // Consultar evaluacionActiva
    $request = Request::create('/api/v1/egresado/postulaciones/'.$postulacion->id.'/evaluacion-activa', 'GET', [
        'proceso_prueba_id' => $procesoPrueba->id,
    ]);
    $request->setUserResolver(fn () => $usuario);
    $controller = app(EvaluacionController::class);
    $response = $controller->evaluacionActiva($request, $postulacion->id);
    $data = $response->getData(true)['data'];

    $resultado->load('dimensiones.dimension');
    $dimsInforme = $resultado->dimensiones->count();

    $this->info('¡Habilidades blandas finalizado y calificado!');
    $this->line("  Puntaje bruto: {$resultado->puntaje_bruto} / {$resultado->puntaje_maximo}");
    $this->line("  Porcentaje normalizado: {$resultado->puntaje_normalizado}%");
    $this->line('  Aprobada: '.($resultado->aprobada ? 'SÍ' : 'NO'));
    $this->line("  Dimensiones con esten en informe: {$dimsInforme}/8");

    $diagEmocional = $data['resultado']['diagnostico_emocional'] ?? null;
    if ($diagEmocional) {
        $this->line('  --- Inteligencia emocional (TMMS, 3 dimensiones) ---');
        foreach ($diagEmocional['dimensiones'] ?? [] as $d) {
            $this->line("    • {$d['nombre']}: {$d['puntaje']}/{$d['puntaje_maximo']} -> {$d['nivel']}");
        }
    }

    $diag = $data['resultado']['diagnostico_habilidades_blandas'] ?? null;
    if ($diag) {
        $this->line('  --- Competencias socioemocionales (5 dimensiones) ---');
        $this->line("  Nivel global: {$diag['nivel_global']}");
        $this->line("  Puntaje: {$diag['puntaje_total']}/{$diag['puntaje_maximo']} ({$diag['porcentaje_global']}%)");
        foreach ($diag['dimensiones'] as $d) {
            $this->line("    • {$d['nombre']}: {$d['puntaje']}/{$d['puntaje_maximo']} ({$d['porcentaje']}%) -> {$d['nivel']}");
        }
    }
})->purpose('Simula Habilidades blandas (49 ítems, 8 dimensiones)');

Artisan::command('talento:calificar-entrevista {id=1}', function (int $id, CalificarEntrevista $calificar) {
    $postulacion = Postulacion::with(['persona', 'proceso'])->findOrFail($id);
    $admin = Usuario::where('correo', 'admin@talento.test')->firstOrFail();

    $etapaEntrevista = $postulacion->etapas()
        ->whereHas('procesoEtapa.etapa', fn ($q) => $q->where('codigo', 'ENTREVISTA'))
        ->firstOrFail();

    $procesoPrueba = ProcesoPrueba::where('proceso_etapa_id', $etapaEntrevista->proceso_etapa_id)
        ->whereHas('prueba', fn ($q) => $q->where('codigo', 'ENTREVISTA'))
        ->firstOrFail();

    $criterios = RubricaCriterio::where('prueba_id', $procesoPrueba->prueba_id)->orderBy('orden')->get();

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

    $this->info('¡Entrevista calificada exitosamente!');
    $this->line("  Puntaje: {$resultado->puntaje_bruto}/{$resultado->puntaje_maximo} ({$resultado->puntaje_normalizado}%)");
    $this->line('  Aprobada: '.($resultado->aprobada ? 'SÍ' : 'NO'));
});

Artisan::command('talento:calcular-ranking {id=1}', function (int $id, CalcularRankingSeleccion $calcular) {
    $proceso = Proceso::with(['convocatoria.carrera'])->findOrFail($id);
    $this->info("Calculando ranking para Proceso #{$proceso->id}: {$proceso->nombre} ({$proceso->convocatoria->carrera->nombre})...");

    $ranking = $calcular->handle($proceso);

    $this->info('=== CUADRO DE HONOR Y RANKING FINAL ===');
    foreach ($ranking as $cand) {
        $this->line(sprintf('  #%d | Postulación #%d (%s) | %s | Puntaje: %6.2f pts | Top: %s',
            $cand['posicion'], $cand['postulacion_id'], $cand['codigo_postulacion'], $cand['postulante']['nombre_completo'], $cand['puntaje_total'], $cand['es_top'] ? 'SÍ (Terna/Quinteto)' : 'NO'
        ));
        $this->line('      Desglose ponderado:');
        foreach ($cand['desglose'] as $d) {
            $this->line(sprintf('        • %-30s | Peso: %2d%% | Norm: %6.2f%% | Aporte: %5.2f pts',
                $d['nombre'], (int) $d['peso'], (float) $d['puntaje_normalizado'], (float) $d['aporte_ponderado']
            ));
        }
    }
});

Artisan::command('talento:adjudicar-seleccion {id=1}', function (int $id, AdjudicarSeleccion $adjudicar) {
    $proceso = Proceso::with(['convocatoria.carrera'])->findOrFail($id);
    $admin = Usuario::where('correo', 'admin@talento.test')->firstOrFail();

    $this->info("Adjudicando plazas para Proceso #{$proceso->id}...");
    $resultado = $adjudicar->handle($admin, $proceso);

    $this->info("¡Selección adjudicada y publicada exitosamente por {$resultado['publicado_por']}!");
    $this->line("  Adjudicados: {$resultado['adjudicados_count']}");
    $this->line("  No adjudicados: {$resultado['no_adjudicados_count']}");
    foreach ($resultado['ranking'] as $r) {
        $this->line(sprintf('  #%d | Postulación #%d (%s) | %s | %6.2f pts | Adjudicado: %s',
            $r['posicion'], $r['postulacion_id'], $r['codigo'], $r['postulante'], (float) $r['puntaje_total'], $r['adjudicado'] ? '🏆 SÍ' : 'NO'
        ));
    }
});

Artisan::command('talento:sembrar-embudo-panel', function () {
    (new EmbudoEvaluacionPanelSeeder)->setCommand($this)->run();
    $this->call('talento:resincronizar-postulaciones');
})->purpose('Crea egresados y candidatos de empresa en fases 6/7 (CV/entrevista) y resincroniza etapas.');

Artisan::command('talento:sembrar-usuarios-demo', function () {
    (new DemoSeeder)->setCommand($this)->run();
    (new EstudiantesPruebaSeeder)->setCommand($this)->run();
    (new EmbudoEvaluacionPanelSeeder)->setCommand($this)->run();
    $this->call('talento:resincronizar-postulaciones');
    $this->info('');
    $this->info('Cuentas: admin@talento.test / admin12345');
    $this->info('Ayudantes: ayudante@talento.test · ayudante2@talento.test / ayudante12345');
    $this->info('Egresados demo: fase5…fase8, cv.panel@, entrevista.panel@, empresa.embudo@ → ver EmbudoEvaluacionPanelSeeder');
})->purpose('Admin, ayudantes, egresados por fase y embudo de panel (entorno local).');

Artisan::command('talento:resincronizar-postulaciones {--id=}', function (
    AvanzarEtapaTrasResultados $avanzar,
) {
    $id = $this->option('id');

    $umbrales = ProcesoPrueba::query()
        ->whereNull('puntaje_minimo')
        ->whereHas('prueba', fn ($q) => $q->whereNotIn('codigo', ['16PF_FORMA_A', '16PF_FORMA_B', 'CV_ANALISIS', 'ENTREVISTA']))
        ->update(['puntaje_minimo' => 50]);

    if ($umbrales > 0) {
        $this->info("Umbrales por defecto (50%) aplicados a {$umbrales} prueba(s) del proceso.");
    }

    $consulta = Postulacion::query()->orderBy('id');
    if ($id !== null && $id !== '') {
        $consulta->where('id', (int) $id);
    }

    $total = 0;
    $noAprobadas = 0;

    foreach ($consulta->cursor() as $postulacion) {
        DB::transaction(function () use ($avanzar, $postulacion) {
            $avanzar->reconciliarPostulacion($postulacion->fresh(['etapas.procesoEtapa.etapa']));
        });

        $postulacion->refresh();
        $total++;
        if ($postulacion->estado === EstadoPostulacion::NoAprobada) {
            $noAprobadas++;
        }

        $this->line(sprintf(
            '  Postulación #%d (%s): %s',
            $postulacion->id,
            $postulacion->codigo,
            $postulacion->estado?->value ?? '—'
        ));
    }

    $this->info("Revisadas: {$total} | No aprobadas tras psico: {$noAprobadas}");
})->purpose('Alinea etapas y cierres según resultados y umbrales de las pruebas.');

Artisan::command('talento:ver-resumen {id=1}', function (int $id) {
    $this->info('Total postulaciones en el sistema: '.Postulacion::count());
    $postulacion = Postulacion::with(['persona.egresados', 'persona.usuario', 'proceso.convocatoria.carrera.facultad', 'etapas.procesoEtapa.etapa'])->findOrFail($id);
    $this->info("=== RESUMEN POSTULACIÓN #{$postulacion->id} ({$postulacion->codigo}) ===");
    $this->line("Candidato: {$postulacion->persona->nombre_completo} (CI: {$postulacion->persona->ci})");
    $egresado = $postulacion->persona->egresados->first();
    $this->line('PPA Egresado: '.($egresado?->ppa ?? 'Sin PPA'));
    $this->line('PPA Mínimo Facultad: '.($postulacion->proceso->convocatoria->carrera->facultad->ppa_minimo ?? 'N/A'));
    $this->line("Carrera: {$postulacion->proceso->convocatoria->carrera->nombre}");
    $this->info('--- ETAPAS ---');
    foreach ($postulacion->etapas->sortBy('procesoEtapa.orden') as $pe) {
        $this->line(sprintf('  [%d] %-25s: %s', $pe->procesoEtapa->orden, $pe->procesoEtapa->etapa->nombre, $pe->estado->value));
    }
    $this->info('--- DOCUMENTOS SUBIDOS ---');
    $docs = $postulacion->documentos()->where('es_vigente', true)->with('tipoDocumento')->get();
    if ($docs->isEmpty()) {
        $this->line('  (Sin documentos vigentes subidos)');
    } else {
        foreach ($docs as $d) {
            $this->line(sprintf('  • %-25s | Estado: %s | Archivo: %s', $d->tipoDocumento?->nombre ?? 'N/A', $d->estado?->value, $d->nombre_original ?? 'archivo'));
        }
    }
    $this->info('--- PRUEBAS CONFIGURADAS EN EL PROCESO ---');
    $procesoEtapas = $postulacion->proceso->etapas()->with(['etapa', 'pruebas.prueba'])->orderBy('orden')->get();
    foreach ($procesoEtapas as $pe) {
        $this->line("  [Etapa {$pe->orden}: {$pe->etapa->nombre}]");
        foreach ($pe->pruebas as $pp) {
            $this->line(sprintf('    -> Prueba: %-30s | Codigo: %-25s | Peso: %2d%% | Min: %s',
                $pp->prueba->nombre, $pp->prueba->codigo, (int) $pp->peso, $pp->puntaje_minimo ?? 'N/A'));
        }
    }
    $this->info('--- PRUEBAS Y CALIFICACIONES REGISTRADAS ---');
    $resultados = ResultadoPrueba::where('postulacion_id', $postulacion->id)->with('procesoPrueba.prueba')->get();
    foreach ($resultados as $r) {
        $prueba = $r->procesoPrueba?->prueba;
        $peso = $r->procesoPrueba?->peso ?? 0;
        $this->line(sprintf('  • %-30s | Bruto: %6.2f/%-6.2f | Norm: %6.2f%% | Peso: %2d%% | Aprobada: %s',
            $prueba?->nombre ?? 'N/A', (float) $r->puntaje_bruto, (float) $r->puntaje_maximo, (float) $r->puntaje_normalizado, (int) $peso, $r->aprobada ? 'SÍ' : 'NO'));
    }
});
