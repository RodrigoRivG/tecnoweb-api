<?php

namespace Database\Seeders\Concerns;

use App\Actions\Evaluacion\AsignarInstrumentosPostulacion;
use App\Actions\Evaluacion\AvanzarEtapaTrasResultados;
use App\Actions\Evaluacion\CalcularRankingSeleccion;
use App\Actions\Evaluacion\CalificarAnalisisCv;
use App\Actions\Evaluacion\CalificarEntrevista;
use App\Actions\Evaluacion\FinalizarIntentoPrueba;
use App\Actions\Evaluacion\GuardarRespuestaIntento;
use App\Actions\Evaluacion\IniciarIntentoPrueba;
use App\Enums\CodigoRol;
use App\Enums\EstadoDocumento;
use App\Enums\EstadoEtapa;
use App\Enums\EstadoPostulacion;
use App\Enums\EstadoUsuario;
use App\Enums\TipoConsentimiento;
use App\Models\Archivo;
use App\Models\AsignacionPrueba;
use App\Models\Carrera;
use App\Models\ParametroSistema;
use App\Models\Postulacion;
use App\Models\Pregunta;
use App\Models\Proceso;
use App\Models\ProcesoPrueba;
use App\Models\ResultadoPrueba;
use App\Models\Rol;
use App\Models\RubricaCriterio;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait SiembraPostulacionEgresado
{
    use SiembraEvaluacionPostulacion;

    /**
     * @param  list<array<string, mixed>>  $candidatos
     */
    protected function seedCandidatosEgresados(array $candidatos): void
    {
        $admin = Usuario::where('correo', 'admin@talento.test')->firstOrFail();

        $iniciarIntento = app(IniciarIntentoPrueba::class);
        $guardarRespuesta = app(GuardarRespuestaIntento::class);
        $finalizarIntento = app(FinalizarIntentoPrueba::class);
        $calificarCv = app(CalificarAnalisisCv::class);
        $calificarEntrevista = app(CalificarEntrevista::class);

        $procesosRanking = [];

        foreach ($candidatos as $datos) {
            $fase = (int) ($datos['meta_fase'] ?? 8);
            $busquedaCarrera = $datos['carrera_like'] ?? '%Ingeniería de Sistemas%';
            $carrera = Carrera::where('nombre', 'like', $busquedaCarrera)->first();
            if ($carrera === null) {
                $this->command?->warn("   Omitido {$datos['correo']}: no hay carrera que coincida con «{$busquedaCarrera}».");

                continue;
            }

            $proceso = Proceso::whereHas('convocatoria', fn ($q) => $q->where('carrera_id', $carrera->id))->first();
            if ($proceso === null) {
                $this->command?->warn("   Omitido {$datos['correo']}: {$carrera->nombre} no tiene convocatoria/proceso (ejecuta DemoSeeder o abre convocatoria).");

                continue;
            }
            $pruebaPpa = $proceso->pruebaPpa();
            $procesosRanking[$proceso->id] = $proceso;

            $this->command?->info("--> FASE {$fase}: {$datos['nombres']} {$datos['apellido_paterno']} ({$datos['correo']}) — {$carrera->nombre}");

            $usuario = Usuario::query()->updateOrCreate(
                ['correo' => $datos['correo']],
                [
                    'rol_id' => Rol::idDe(CodigoRol::Egresado),
                    'password_hash' => Hash::make($datos['password'] ?? 'password123'),
                    'estado' => EstadoUsuario::Activo,
                ]
            );

            $persona = $usuario->persona()->updateOrCreate(
                ['usuario_id' => $usuario->id],
                [
                    'nombres' => $datos['nombres'],
                    'apellido_paterno' => $datos['apellido_paterno'],
                    'apellido_materno' => $datos['apellido_materno'],
                    'ci_numero' => $datos['ci_numero'],
                    'ci_complemento' => $datos['ci_complemento'] ?? '',
                    'ci_expedido' => $datos['ci_expedido'],
                    'fecha_nacimiento' => $datos['fecha_nacimiento'],
                    'telefono' => $datos['telefono'],
                    'correo_contacto' => $datos['correo'],
                ]
            );

            $persona->egresados()->updateOrCreate(
                ['carrera_id' => $carrera->id],
                [
                    'registro_universitario' => $datos['registro_universitario'],
                    'anio_egreso' => $datos['anio_egreso'],
                    'ppa' => $datos['ppa'],
                ]
            );

            $consentimiento = [
                'version_politica' => ParametroSistema::valor(ParametroSistema::VERSION_POLITICA_PRIVACIDAD) ?? '1.0',
                'ip_origen' => '127.0.0.1',
            ];

            $persona->consentimientos()->updateOrCreate(
                ['tipo' => TipoConsentimiento::TratamientoDatos],
                [...$consentimiento, 'otorgado' => true]
            );
            $persona->consentimientos()->updateOrCreate(
                ['tipo' => TipoConsentimiento::PublicacionPerfil],
                [...$consentimiento, 'otorgado' => true]
            );

            $postulacion = $persona->postulaciones()->where('proceso_id', $proceso->id)->first();
            if (! $postulacion) {
                $postulacion = $persona->postulaciones()->create([
                    'codigo' => Postulacion::generarCodigo('T', $proceso->convocatoria->gestion),
                    'proceso_id' => $proceso->id,
                    'estado' => EstadoPostulacion::EnProceso,
                ]);
            }

            $etapasProceso = $proceso->etapas()->where('activa', true)->orderBy('orden')->get();
            foreach ($etapasProceso as $pe) {
                $postulacion->etapas()->firstOrCreate(
                    ['proceso_etapa_id' => $pe->id],
                    ['estado' => EstadoEtapa::Bloqueada]
                );
            }

            if ($pruebaPpa) {
                ResultadoPrueba::updateOrCreate(
                    [
                        'postulacion_id' => $postulacion->id,
                        'proceso_prueba_id' => $pruebaPpa->id,
                    ],
                    [
                        'puntaje_bruto' => $datos['ppa'],
                        'puntaje_maximo' => 100.00,
                        'umbral_aplicado' => 51.00,
                        'aprobada' => true,
                        'calificado_en' => now(),
                    ]
                );
            }

            foreach ($proceso->tiposDocumento as $tipo) {
                $contenido = "%PDF-1.4 Documento oficial {$tipo->nombre} del postulante {$datos['nombres']} {$datos['apellido_paterno']}";
                $clave = "postulaciones/{$postulacion->codigo}/".Str::uuid().'.pdf';
                Storage::disk('local')->put($clave, $contenido);

                $archivo = Archivo::create([
                    'nombre_original' => Str::slug($tipo->nombre)."_{$datos['apellido_paterno']}.pdf",
                    'clave_almacenamiento' => $clave,
                    'mime_type' => 'application/pdf',
                    'tamano_bytes' => strlen($contenido),
                    'hash_sha256' => hash('sha256', $contenido),
                    'subido_por' => $usuario->id,
                ]);

                $postulacion->documentos()->where('tipo_documento_id', $tipo->id)->update(['es_vigente' => false]);
                $postulacion->documentos()->create([
                    'tipo_documento_id' => $tipo->id,
                    'archivo_id' => $archivo->id,
                    'estado' => EstadoDocumento::Validado,
                    'es_vigente' => true,
                ]);
            }

            $etapas = $postulacion->etapas()->with('procesoEtapa.etapa')->get()->keyBy(fn ($e) => $e->procesoEtapa->etapa->codigo);

            $etapas['INSCRIPCION']?->update(['estado' => EstadoEtapa::Aprobada, 'finalizada_en' => now()]);
            $etapas['EVALUACION_TECNICA']?->update(['estado' => EstadoEtapa::Aprobada, 'finalizada_en' => now()]);
            $etapas['REQUISITOS']?->update(['estado' => EstadoEtapa::Aprobada, 'finalizada_en' => now()]);

            $calidadPsico = (float) ($datos['calidad_psico'] ?? 1.0);

            $etapaPsico = $etapas['EVALUACION_PSICOLOGICA'];
            $asignarInstrumentos = app(AsignarInstrumentosPostulacion::class);
            $asignarInstrumentos->handle($postulacion->refresh());

            $idsEtapaPsico = ProcesoPrueba::query()
                ->where('proceso_etapa_id', $etapaPsico->proceso_etapa_id)
                ->pluck('id');

            $pruebasPsico = AsignacionPrueba::query()
                ->with('procesoPrueba.prueba')
                ->where('postulacion_id', $postulacion->id)
                ->whereIn('proceso_prueba_id', $idsEtapaPsico)
                ->get()
                ->pluck('procesoPrueba')
                ->filter()
                ->sortBy('orden')
                ->values();

            $etapaPsico->update(['estado' => EstadoEtapa::Pendiente, 'iniciada_en' => now(), 'finalizada_en' => null]);
            foreach ($pruebasPsico as $pp) {
                $this->limpiarIntento($postulacion->id, $pp->id);

                $postulacion->etapas()
                    ->where('proceso_etapa_id', $pp->proceso_etapa_id)
                    ->update([
                        'estado' => EstadoEtapa::Pendiente,
                        'iniciada_en' => now(),
                        'finalizada_en' => null,
                    ]);

                $intento = $iniciarIntento->handle($usuario, $postulacion, $pp);
                $intento->load('respuestas');
                $preguntas = Pregunta::query()
                    ->whereIn('id', $intento->respuestas->pluck('pregunta_id'))
                    ->with('opciones')
                    ->orderBy('orden')
                    ->get();

                if ($preguntas->isEmpty()) {
                    $preguntas = Pregunta::where('prueba_id', $pp->prueba_id)->with('opciones')->orderBy('orden')->get();
                }

                foreach ($preguntas as $p) {
                    $opcion = $this->opcionPorCalidad($p->opciones, $calidadPsico);
                    if ($opcion) {
                        $guardarRespuesta->handle($usuario, $intento, $p->id, $opcion->id);
                    }
                }
                $finalizarIntento->handle($usuario, $intento);
            }

            app(AvanzarEtapaTrasResultados::class)->reconciliarPostulacion($postulacion->refresh());

            if ($postulacion->estado === EstadoPostulacion::NoAprobada) {
                $this->command?->info("   -> Postulación #{$postulacion->id}: descartada en evaluación psicológica (umbrales).");

                continue;
            }

            if (isset($etapas['HABILIDADES_BLANDAS'])) {
                $etapaBlandas = $etapas['HABILIDADES_BLANDAS'];
                $ppBlandas = ProcesoPrueba::where('proceso_etapa_id', $etapaBlandas->proceso_etapa_id)
                    ->whereHas('prueba', fn ($q) => $q->where('codigo', 'TEST_HABILIDADES_BLANDAS'))
                    ->firstOrFail();

                if ($fase === 5) {
                    $this->limpiarIntento($postulacion->id, $ppBlandas->id);
                    $etapaBlandas->update(['estado' => EstadoEtapa::Pendiente, 'iniciada_en' => now(), 'finalizada_en' => null]);
                    $this->command?->info("   -> Postulación #{$postulacion->id} en FASE 5 (habilidades blandas pendientes).");

                    continue;
                }

                $this->limpiarIntento($postulacion->id, $ppBlandas->id);
                $etapaBlandas->update(['estado' => EstadoEtapa::Pendiente, 'iniciada_en' => now(), 'finalizada_en' => null]);
                $intentoBlandas = $iniciarIntento->handle($usuario, $postulacion, $ppBlandas);

                $preguntasBlandas = Pregunta::where('prueba_id', $ppBlandas->prueba_id)->with('opciones')->orderBy('orden')->get();
                $patron = $datos['likert_patron'] ?? [5, 5, 4, 5, 5];
                $idx = 0;
                foreach ($preguntasBlandas as $p) {
                    $val = $patron[$idx % count($patron)];
                    $opcion = $p->opciones->firstWhere('etiqueta', (string) $val) ?? $p->opciones->sortByDesc('puntaje')->first();
                    if ($opcion) {
                        $guardarRespuesta->handle($usuario, $intentoBlandas, $p->id, $opcion->id);
                    }
                    $idx++;
                }
                $finalizarIntento->handle($usuario, $intentoBlandas);
                $etapaBlandas->update(['estado' => EstadoEtapa::Aprobada, 'finalizada_en' => now()]);
            }

            if ($fase === 5 && ! isset($etapas['HABILIDADES_BLANDAS'])) {
                $this->command?->info("   -> Postulación #{$postulacion->id}: habilidades blandas integradas en psicológica.");

                continue;
            }

            $etapaCv = $etapas['ANALISIS_CV'];
            $etapaCv->update(['estado' => EstadoEtapa::Pendiente, 'iniciada_en' => now(), 'finalizada_en' => null]);

            if ($fase === 6) {
                $this->command?->info("   -> Postulación #{$postulacion->id} en FASE 6 (CV pendiente).");

                continue;
            }

            $notaCv = (float) ($datos['nota_cv_criterio'] ?? 9.5);
            $ppCv = ProcesoPrueba::where('proceso_etapa_id', $etapaCv->proceso_etapa_id)->first();
            $criteriosCv = RubricaCriterio::where('prueba_id', $ppCv->prueba_id)->get();
            $califsCv = [];
            foreach ($criteriosCv as $c) {
                $califsCv[] = [
                    'criterio_id' => $c->id,
                    'puntaje' => $notaCv,
                    'comentario' => "Desempeño evaluado en {$c->nombre}.",
                ];
            }
            $calificarCv->handle($admin, $postulacion, [
                'calificaciones' => $califsCv,
                'observaciones' => $datos['observaciones_cv'] ?? 'Expediente revisado en simulación de datos.',
            ]);

            $etapaEntrevista = $etapas['ENTREVISTA'];
            $etapaEntrevista->update(['estado' => EstadoEtapa::Pendiente, 'iniciada_en' => now(), 'finalizada_en' => null]);

            if ($fase === 7) {
                $this->command?->info("   -> Postulación #{$postulacion->id} en FASE 7 (entrevista pendiente).");

                continue;
            }

            $notaEntrevista = (float) ($datos['nota_entrevista_criterio'] ?? 9.8);
            $ppEntrevista = ProcesoPrueba::where('proceso_etapa_id', $etapaEntrevista->proceso_etapa_id)->first();
            $criteriosEntrevista = RubricaCriterio::where('prueba_id', $ppEntrevista->prueba_id)->get();
            $califsEntrevista = [];
            foreach ($criteriosEntrevista as $c) {
                $califsEntrevista[] = [
                    'criterio_id' => $c->id,
                    'puntaje' => $notaEntrevista,
                    'comentario' => "Desempeño en {$c->nombre}.",
                ];
            }
            $calificarEntrevista->handle($admin, $postulacion, [
                'calificaciones' => $califsEntrevista,
                'observaciones' => $datos['observaciones_entrevista'] ?? 'Entrevista simulada para datos de prueba.',
            ]);

            $etapas['SELECCION']?->update([
                'estado' => EstadoEtapa::Aprobada,
                'iniciada_en' => now(),
                'finalizada_en' => now(),
            ]);
            $postulacion->update(['estado' => EstadoPostulacion::Aprobada]);

            $this->command?->info("   -> Postulación #{$postulacion->id} completa (etapa selección aprobada).");
        }

        foreach ($procesosRanking as $proceso) {
            app(CalcularRankingSeleccion::class)->handle($proceso);
            $this->command?->info("Ranking actualizado para proceso #{$proceso->id} ({$proceso->nombre}).");
        }
    }
}
