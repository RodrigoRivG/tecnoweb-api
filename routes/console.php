<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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




