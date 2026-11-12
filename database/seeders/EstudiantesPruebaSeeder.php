<?php

namespace Database\Seeders;

use App\Actions\Evaluacion\CalificarAnalisisCv;
use App\Actions\Evaluacion\CalificarEntrevista;
use App\Actions\Evaluacion\FinalizarIntentoPrueba;
use App\Actions\Evaluacion\GuardarRespuestaIntento;
use App\Actions\Evaluacion\IniciarIntentoPrueba;
use App\Enums\CodigoRol;
use App\Enums\DepartamentoCi;
use App\Enums\EstadoDocumento;
use App\Enums\EstadoEtapa;
use App\Enums\EstadoPostulacion;
use App\Enums\EstadoUsuario;
use App\Enums\TipoConsentimiento;
use App\Models\Archivo;
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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EstudiantesPruebaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $carrera = Carrera::where('nombre', 'like', '%Ingeniería de Sistemas%')->firstOrFail();
        $proceso = Proceso::whereHas('convocatoria', fn ($q) => $q->where('carrera_id', $carrera->id))->firstOrFail();
        $pruebaPpa = $proceso->pruebaPpa();
        $admin = Usuario::where('correo', 'admin@talento.test')->firstOrFail();

        $iniciarIntento = app(IniciarIntentoPrueba::class);
        $guardarRespuesta = app(GuardarRespuestaIntento::class);
        $finalizarIntento = app(FinalizarIntentoPrueba::class);
        $calificarCv = app(CalificarAnalisisCv::class);
        $calificarEntrevista = app(CalificarEntrevista::class);

        // 4 Candidatos estratégicos para cada fase del embudo:
        $candidatos = [
            [
                'meta_fase' => 5, // Listo para que el usuario rinda personalmente Habilidades Blandas
                'correo' => 'fase5.blandas@talento.test',
                'password' => 'password123',
                'nombres' => 'Alejandro',
                'apellido_paterno' => 'Justiniano',
                'apellido_materno' => 'Paz',
                'ci_numero' => '8123451',
                'ci_complemento' => '',
                'ci_expedido' => DepartamentoCi::SantaCruz,
                'fecha_nacimiento' => '2001-03-10',
                'telefono' => '71122334',
                'registro_universitario' => '219011223',
                'anio_egreso' => 2025,
                'ppa' => '91.50',
            ],
            [
                'meta_fase' => 6, // Listo para calificar Análisis de CV (Etapa 6 PENDIENTE)
                'correo' => 'fase6.cv@talento.test',
                'password' => 'password123',
                'nombres' => 'Beatriz',
                'apellido_paterno' => 'Salguero',
                'apellido_materno' => 'Morales',
                'ci_numero' => '8234562',
                'ci_complemento' => '',
                'ci_expedido' => DepartamentoCi::SantaCruz,
                'fecha_nacimiento' => '2002-07-19',
                'telefono' => '72233445',
                'registro_universitario' => '219022334',
                'anio_egreso' => 2025,
                'ppa' => '94.00',
                'likert_patron' => [5, 5, 4, 5, 5],
            ],
            [
                'meta_fase' => 7, // Listo para calificar Entrevista (Etapa 7 PENDIENTE)
                'correo' => 'fase7.entrevista@talento.test',
                'password' => 'password123',
                'nombres' => 'Cristian',
                'apellido_paterno' => 'Torrico',
                'apellido_materno' => 'Dorado',
                'ci_numero' => '8345673',
                'ci_complemento' => '',
                'ci_expedido' => DepartamentoCi::SantaCruz,
                'fecha_nacimiento' => '2001-11-25',
                'telefono' => '73344556',
                'registro_universitario' => '219033445',
                'anio_egreso' => 2025,
                'ppa' => '95.80',
                'likert_patron' => [5, 5, 5, 5, 4],
            ],
            [
                'meta_fase' => 8, // Todo Aprobado y Adjudicado con notas máximas
                'correo' => 'fase8.adjudicada@talento.test',
                'password' => 'password123',
                'nombres' => 'Daniela',
                'apellido_paterno' => 'Rios',
                'apellido_materno' => 'Aguilera',
                'ci_numero' => '8456784',
                'ci_complemento' => '',
                'ci_expedido' => DepartamentoCi::SantaCruz,
                'fecha_nacimiento' => '2002-04-12',
                'telefono' => '74455667',
                'registro_universitario' => '219044556',
                'anio_egreso' => 2025,
                'ppa' => '98.50',
                'likert_patron' => [5, 5, 5, 5, 5],
            ],
        ];

        foreach ($candidatos as $datos) {
            $fase = $datos['meta_fase'];
            $this->command?->info("--> Configurando estudiante para FASE {$fase}: {$datos['nombres']} {$datos['apellido_paterno']} ({$datos['correo']})...");

            $usuario = Usuario::query()->updateOrCreate(
                ['correo' => $datos['correo']],
                [
                    'rol_id' => Rol::idDe(CodigoRol::Egresado),
                    'password_hash' => Hash::make($datos['password']),
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
                    'ci_complemento' => $datos['ci_complemento'],
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

            // 1. Crear o recuperar Postulación
            $postulacion = $persona->postulaciones()->where('proceso_id', $proceso->id)->first();
            if (! $postulacion) {
                $postulacion = $persona->postulaciones()->create([
                    'codigo' => Postulacion::generarCodigo('T', $proceso->convocatoria->gestion),
                    'proceso_id' => $proceso->id,
                    'estado' => EstadoPostulacion::EnProceso,
                ]);
            }

            // Sincronizar etapas del proceso
            $etapasProceso = $proceso->etapas()->where('activa', true)->orderBy('orden')->get();
            foreach ($etapasProceso as $pe) {
                $postulacion->etapas()->firstOrCreate(
                    ['proceso_etapa_id' => $pe->id],
                    ['estado' => EstadoEtapa::Bloqueada]
                );
            }

            // 2. Etapa 2: PPA
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

            // 3. Etapa 3: Documentos oficiales
            $tiposDoc = $proceso->tiposDocumento;
            foreach ($tiposDoc as $tipo) {
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

            // Etapas 1, 2, 3 siempre Aprobadas para todos
            $etapas['INSCRIPCION']?->update(['estado' => EstadoEtapa::Aprobada, 'finalizada_en' => now()]);
            $etapas['EVALUACION_TECNICA']?->update(['estado' => EstadoEtapa::Aprobada, 'finalizada_en' => now()]);
            $etapas['REQUISITOS']?->update(['estado' => EstadoEtapa::Aprobada, 'finalizada_en' => now()]);

            // 4. Etapa 4: Evaluación Psicológica
            $etapaPsico = $etapas['EVALUACION_PSICOLOGICA'];
            $pruebasPsico = ProcesoPrueba::where('proceso_etapa_id', $etapaPsico->proceso_etapa_id)->with('prueba')->orderBy('orden')->get();

            // Resolver los 6 tests psicológicos con nota perfecta
            $etapaPsico->update(['estado' => EstadoEtapa::Pendiente, 'iniciada_en' => now()]);
            foreach ($pruebasPsico as $pp) {
                $intentoPrevio = \App\Models\IntentoPrueba::where('postulacion_id', $postulacion->id)->where('proceso_prueba_id', $pp->id)->first();
                if ($intentoPrevio) {
                    \App\Models\RespuestaIntento::where('intento_id', $intentoPrevio->id)->delete();
                    ResultadoPrueba::where('intento_id', $intentoPrevio->id)->delete();
                    $intentoPrevio->delete();
                }

                $intento = $iniciarIntento->handle($usuario, $postulacion, $pp);
                $preguntas = Pregunta::where('prueba_id', $pp->prueba_id)->with('opciones')->orderBy('orden')->get();
                foreach ($preguntas as $p) {
                    $mejorOpcion = $p->opciones->sortByDesc('puntaje')->first();
                    if ($mejorOpcion) {
                        $guardarRespuesta->handle($usuario, $intento, $p->id, $mejorOpcion->id);
                    }
                }
                $finalizarIntento->handle($usuario, $intento);
            }
            $etapaPsico->update(['estado' => EstadoEtapa::Aprobada, 'finalizada_en' => now()]);

            // 5. Etapa 5: Habilidades Blandas
            $etapaBlandas = $etapas['HABILIDADES_BLANDAS'];
            $ppBlandas = ProcesoPrueba::where('proceso_etapa_id', $etapaBlandas->proceso_etapa_id)
                ->whereHas('prueba', fn ($q) => $q->where('codigo', 'TEST_HABILIDADES_BLANDAS'))
                ->firstOrFail();

            if ($fase === 5) {
                // Dejar Etapa 5 PENDIENTE y limpia, para que el usuario rinda el test
                $intentoPrevio = \App\Models\IntentoPrueba::where('postulacion_id', $postulacion->id)->where('proceso_prueba_id', $ppBlandas->id)->first();
                if ($intentoPrevio) {
                    \App\Models\RespuestaIntento::where('intento_id', $intentoPrevio->id)->delete();
                    ResultadoPrueba::where('intento_id', $intentoPrevio->id)->delete();
                    $intentoPrevio->delete();
                }
                $etapaBlandas->update(['estado' => EstadoEtapa::Pendiente, 'iniciada_en' => now()]);
                $this->command?->info("   -> Postulación #{$postulacion->id} lista en FASE 5 (Habilidades Blandas ▶ PENDIENTE).");
                continue;
            }

            // Para fase >= 6, resolver Habilidades Blandas con nota alta
            $intentoPrevio = \App\Models\IntentoPrueba::where('postulacion_id', $postulacion->id)->where('proceso_prueba_id', $ppBlandas->id)->first();
            if ($intentoPrevio) {
                \App\Models\RespuestaIntento::where('intento_id', $intentoPrevio->id)->delete();
                ResultadoPrueba::where('intento_id', $intentoPrevio->id)->delete();
                $intentoPrevio->delete();
            }

            $etapaBlandas->update(['estado' => EstadoEtapa::Pendiente, 'iniciada_en' => now()]);
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

            // 6. Etapa 6: Análisis de CV
            $etapaCv = $etapas['ANALISIS_CV'];
            $etapaCv->update(['estado' => EstadoEtapa::Pendiente, 'iniciada_en' => now()]);

            if ($fase === 6) {
                $this->command?->info("   -> Postulación #{$postulacion->id} lista en FASE 6 (Análisis de CV ▶ PENDIENTE).");
                continue;
            }

            // Para fase >= 7, calificar CV con rúbrica alta (38/40 pts)
            $ppCv = ProcesoPrueba::where('proceso_etapa_id', $etapaCv->proceso_etapa_id)->first();
            $criteriosCv = RubricaCriterio::where('prueba_id', $ppCv->prueba_id)->get();
            $califsCv = [];
            foreach ($criteriosCv as $c) {
                $califsCv[] = [
                    'criterio_id' => $c->id,
                    'puntaje' => 9.5,
                    'comentario' => "Excelente formación y méritos en {$c->nombre}.",
                ];
            }
            $calificarCv->handle($admin, $postulacion, [
                'calificaciones' => $califsCv,
                'observaciones' => 'Expediente sobresaliente con alta pertinencia profesional.',
            ]);

            // 7. Etapa 7: Entrevista Personal
            $etapaEntrevista = $etapas['ENTREVISTA'];
            $etapaEntrevista->update(['estado' => EstadoEtapa::Pendiente, 'iniciada_en' => now()]);

            if ($fase === 7) {
                $this->command?->info("   -> Postulación #{$postulacion->id} lista en FASE 7 (Entrevista ▶ PENDIENTE).");
                continue;
            }

            // Para fase 8, calificar Entrevista con nota de excelencia (39/40 pts)
            $ppEntrevista = ProcesoPrueba::where('proceso_etapa_id', $etapaEntrevista->proceso_etapa_id)->first();
            $criteriosEntrevista = RubricaCriterio::where('prueba_id', $ppEntrevista->prueba_id)->get();
            $califsEntrevista = [];
            foreach ($criteriosEntrevista as $c) {
                $califsEntrevista[] = [
                    'criterio_id' => $c->id,
                    'puntaje' => 9.8,
                    'comentario' => "Desenvolvimiento sobresaliente en {$c->nombre}.",
                ];
            }
            $calificarEntrevista->handle($admin, $postulacion, [
                'calificaciones' => $califsEntrevista,
                'observaciones' => 'Postulante con liderazgo, solvencia técnica y destacada dicción.',
            ]);

            // 8. Etapa 8: Selección y Adjudicación APROBADA
            $etapaSeleccion = $etapas['SELECCION'];
            $etapaSeleccion->update([
                'estado' => EstadoEtapa::Aprobada,
                'iniciada_en' => now(),
                'finalizada_en' => now(),
            ]);
            $postulacion->update(['estado' => EstadoPostulacion::Aprobada]);

            $this->command?->info("   -> Postulación #{$postulacion->id} lista en FASE 8 (🏆 TODO APROBADO Y ADJUDICADA).");
        }

        // Recalcular el ranking final consolidado del proceso
        app(\App\Actions\Evaluacion\CalcularRankingSeleccion::class)->handle($proceso);
        $this->command?->info("\n¡Todos los usuarios por fase han sido generados y el ranking ha sido actualizado exitosamente!");
    }
}
