<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\EstadoProceso;
use App\Enums\TipoProceso;
use App\Enums\UmbralOrigen;
use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\ConvocatoriaTalento;
use App\Models\Etapa;
use App\Models\Proceso;
use App\Models\Prueba;
use App\Models\TipoDocumento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ConvocatoriaAdminController extends Controller
{
    /**
     * Lista todas las convocatorias de talento registradas con filtros.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Proceso::query()
            ->where('tipo', TipoProceso::Talento)
            ->with([
                'convocatoria.carrera.facultad',
                'creadoPor:id,correo',
            ])
            ->withCount(['postulaciones', 'rankings']);

        if ($request->filled('carrera_id')) {
            $query->whereHas('convocatoria', fn ($q) => $q->where('carrera_id', $request->integer('carrera_id')));
        }

        if ($request->filled('facultad_id')) {
            $query->whereHas('convocatoria.carrera', fn ($q) => $q->where('facultad_id', $request->integer('facultad_id')));
        }

        if ($request->filled('gestion')) {
            $query->whereHas('convocatoria', fn ($q) => $q->where('gestion', $request->integer('gestion')));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        if ($request->filled('q')) {
            $termino = '%' . trim($request->string('q')) . '%';
            $query->where(function ($q) use ($termino) {
                $q->where('codigo', 'ilike', $termino)
                    ->orWhere('nombre', 'ilike', $termino)
                    ->orWhereHas('convocatoria.carrera', fn ($qc) => $qc->where('nombre', 'ilike', $termino));
            });
        }

        $procesos = $query->orderBy('id', 'desc')->get();

        $hoy = today()->toDateString();

        return response()->json([
            'data' => $procesos->map(function (Proceso $p) use ($hoy) {
                $c = $p->convocatoria;
                $carrera = $c?->carrera;
                $facultad = $carrera?->facultad;

                $apertura = $c?->fecha_apertura ? Carbon::parse($c->fecha_apertura)->toDateString() : null;
                $cierre = $c?->fecha_cierre ? Carbon::parse($c->fecha_cierre)->toDateString() : null;

                $estaVigente = $p->estado === EstadoProceso::Abierto
                    && ($apertura === null || $apertura <= $hoy)
                    && ($cierre === null || $cierre >= $hoy);

                return [
                    'proceso_id' => $p->id,
                    'codigo' => $p->codigo,
                    'nombre' => $p->nombre,
                    'estado' => $p->estado->value,
                    'top_n' => $p->top_n,
                    'creado_por' => $p->creadoPor?->correo,
                    'created_at' => $p->created_at?->toIso8601String(),
                    'carrera' => $carrera ? [
                        'id' => $carrera->id,
                        'nombre' => $carrera->nombre,
                        'codigo' => $carrera->codigo,
                        'ppa_minimo' => (float) ($carrera->ppa_minimo ?? $facultad?->ppa_minimo ?? 51.0),
                    ] : null,
                    'facultad' => $facultad ? [
                        'id' => $facultad->id,
                        'nombre' => $facultad->nombre,
                        'sigla' => $facultad->sigla,
                    ] : null,
                    'convocatoria' => $c ? [
                        'gestion' => $c->gestion,
                        'numero' => $c->numero,
                        'fecha_apertura' => $apertura,
                        'fecha_cierre' => $cierre,
                        'vigencia_perfil_meses' => $c->vigencia_perfil_meses,
                    ] : null,
                    'postulaciones_count' => $p->postulaciones_count,
                    'rankings_count' => $p->rankings_count,
                    'esta_vigente' => $estaVigente,
                ];
            }),
        ]);
    }

    /**
     * Muestra el detalle de una convocatoria específica.
     */
    public function show(int $procesoId): JsonResponse
    {
        $p = Proceso::where('tipo', TipoProceso::Talento)
            ->with([
                'convocatoria.carrera.facultad',
                'creadoPor:id,correo',
                'etapas.etapa',
                'etapas.pruebas.prueba',
                'tiposDocumento',
            ])
            ->withCount(['postulaciones', 'rankings'])
            ->findOrFail($procesoId);

        $c = $p->convocatoria;
        $carrera = $c?->carrera;
        $facultad = $carrera?->facultad;
        $hoy = today()->toDateString();

        $apertura = $c?->fecha_apertura ? Carbon::parse($c->fecha_apertura)->toDateString() : null;
        $cierre = $c?->fecha_cierre ? Carbon::parse($c->fecha_cierre)->toDateString() : null;

        $estaVigente = $p->estado === EstadoProceso::Abierto
            && ($apertura === null || $apertura <= $hoy)
            && ($cierre === null || $cierre >= $hoy);

        return response()->json([
            'data' => [
                'proceso_id' => $p->id,
                'codigo' => $p->codigo,
                'nombre' => $p->nombre,
                'estado' => $p->estado->value,
                'top_n' => $p->top_n,
                'creado_por' => $p->creadoPor?->correo,
                'created_at' => $p->created_at?->toIso8601String(),
                'carrera' => $carrera ? [
                    'id' => $carrera->id,
                    'nombre' => $carrera->nombre,
                    'codigo' => $carrera->codigo,
                    'ppa_minimo' => (float) ($carrera->ppa_minimo ?? $facultad?->ppa_minimo ?? 51.0),
                ] : null,
                'facultad' => $facultad ? [
                    'id' => $facultad->id,
                    'nombre' => $facultad->nombre,
                    'sigla' => $facultad->sigla,
                ] : null,
                'convocatoria' => $c ? [
                    'gestion' => $c->gestion,
                    'numero' => $c->numero,
                    'fecha_apertura' => $apertura,
                    'fecha_cierre' => $cierre,
                    'vigencia_perfil_meses' => $c->vigencia_perfil_meses,
                ] : null,
                'postulaciones_count' => $p->postulaciones_count,
                'rankings_count' => $p->rankings_count,
                'esta_vigente' => $estaVigente,
                'etapas' => $p->etapas->map(fn ($e) => [
                    'id' => $e->id,
                    'orden' => $e->orden,
                    'nombre' => $e->etapa?->nombre,
                    'codigo' => $e->etapa?->codigo,
                    'pruebas' => $e->pruebas->map(fn ($pr) => [
                        'id' => $pr->id,
                        'nombre' => $pr->prueba?->nombre,
                        'codigo' => $pr->prueba?->codigo,
                        'peso' => (float) $pr->peso,
                        'obligatoria' => $pr->obligatoria,
                    ]),
                ]),
                'requisitos' => $p->tiposDocumento->map(fn ($td) => [
                    'id' => $td->id,
                    'codigo' => $td->codigo,
                    'nombre' => $td->nombre,
                    'obligatorio' => (bool) ($td->requisito?->obligatorio ?? true),
                ]),
            ],
        ]);
    }

    /**
     * Registra una nueva Convocatoria de Talento Universitario con su Proceso y Etapas.
     */
    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'carrera_id' => ['required', 'integer', Rule::exists(Carrera::class, 'id')],
            'gestion' => ['required', 'integer', 'min:2020', 'max:2050'],
            'numero' => ['nullable', 'integer', 'min:1'],
            'nombre' => ['nullable', 'string', 'max:200'],
            'top_n' => ['nullable', 'integer', 'min:1', 'max:50'],
            'fecha_apertura' => ['required', 'date'],
            'fecha_cierre' => ['nullable', 'date', 'after_or_equal:fecha_apertura'],
            'vigencia_perfil_meses' => ['nullable', 'integer', 'min:1', 'max:60'],
            'estado' => ['nullable', Rule::in(['BORRADOR', 'ABIERTO', 'EN_EVALUACION', 'CERRADO'])],
        ]);

        $carrera = Carrera::with('facultad')->findOrFail($datos['carrera_id']);
        $gestion = (int) $datos['gestion'];

        // Determinar número de convocatoria
        if (! empty($datos['numero'])) {
            $numero = (int) $datos['numero'];
        } else {
            $ultimoNumero = ConvocatoriaTalento::where('carrera_id', $carrera->id)
                ->where('gestion', $gestion)
                ->max('numero');
            $numero = $ultimoNumero ? $ultimoNumero + 1 : 1;
        }

        // Validar unicidad (carrera_id, gestion, numero)
        $existe = ConvocatoriaTalento::where('carrera_id', $carrera->id)
            ->where('gestion', $gestion)
            ->where('numero', $numero)
            ->exists();

        if ($existe) {
            return response()->json([
                'mensaje' => "Ya existe la convocatoria Nº {$numero} de la gestión {$gestion} para la carrera {$carrera->nombre}.",
                'errors' => ['numero' => ["Ya existe la convocatoria Nº {$numero} para esta carrera y gestión."]],
            ], 422);
        }

        return DB::transaction(function () use ($request, $datos, $carrera, $gestion, $numero) {
            $codigoBase = sprintf('TAL-%d-%03d', $gestion, $carrera->id);
            $codigo = $numero > 1 ? "{$codigoBase}-{$numero}" : $codigoBase;

            // Asegurar unicidad de código de proceso
            $i = 1;
            $codigoOriginal = $codigo;
            while (Proceso::where('codigo', $codigo)->exists()) {
                $codigo = "{$codigoOriginal}-{$i}";
                $i++;
            }

            $nombre = ! empty($datos['nombre'])
                ? trim($datos['nombre'])
                : "Talento {$carrera->nombre} {$gestion}" . ($numero > 1 ? " (Conv. {$numero})" : '');

            $estado = ! empty($datos['estado'])
                ? EstadoProceso::from($datos['estado'])
                : EstadoProceso::Abierto;

            // 1. Crear Proceso
            $proceso = Proceso::create([
                'codigo' => $codigo,
                'tipo' => TipoProceso::Talento,
                'nombre' => $nombre,
                'estado' => $estado,
                'top_n' => $datos['top_n'] ?? 5,
                'creado_por' => $request->user()->id,
            ]);

            // 2. Crear ConvocatoriaTalento
            $convocatoria = ConvocatoriaTalento::create([
                'proceso_id' => $proceso->id,
                'carrera_id' => $carrera->id,
                'gestion' => $gestion,
                'numero' => $numero,
                'fecha_apertura' => $datos['fecha_apertura'],
                'fecha_cierre' => $datos['fecha_cierre'] ?? null,
                'vigencia_perfil_meses' => $datos['vigencia_perfil_meses'] ?? 12,
            ]);

            // 3. Crear las 8 Etapas del Embudo y asociar sus pruebas estándar
            $etapasCatalogo = Etapa::pluck('id', 'codigo');
            $pruebasCatalogo = Prueba::pluck('id', 'codigo');

            $etapasConfig = [
                'INSCRIPCION' => [
                    'orden' => 1,
                    'pruebas' => [],
                ],
                'EVALUACION_TECNICA' => [
                    'orden' => 2,
                    'pruebas' => [
                        ['codigo' => 'PPA', 'peso' => 20.00, 'umbral_origen' => UmbralOrigen::Facultad],
                    ],
                ],
                'REQUISITOS' => [
                    'orden' => 3,
                    'pruebas' => [],
                ],
                'EVALUACION_PSICOLOGICA' => [
                    'orden' => 4,
                    'pruebas' => [
                        ['codigo' => 'TEST_PERSONALIDAD', 'peso' => 10.00, 'umbral_origen' => UmbralOrigen::Fijo],
                        ['codigo' => 'TEST_INTELIGENCIA', 'peso' => 8.00, 'umbral_origen' => UmbralOrigen::Fijo],
                        ['codigo' => 'TEST_INTELIGENCIA_EMOCIONAL', 'peso' => 7.00, 'umbral_origen' => UmbralOrigen::Fijo],
                        ['codigo' => 'TEST_LECTURA_COMPRENSIVA', 'peso' => 5.00, 'umbral_origen' => UmbralOrigen::Fijo],
                        ['codigo' => 'TEST_PURDUE_NO_VERBAL', 'peso' => 5.00, 'umbral_origen' => UmbralOrigen::Fijo],
                    ],
                ],
                'HABILIDADES_BLANDAS' => [
                    'orden' => 5,
                    'pruebas' => [
                        ['codigo' => 'TEST_HABILIDADES_BLANDAS', 'peso' => 15.00, 'umbral_origen' => UmbralOrigen::Fijo],
                    ],
                ],
                'ANALISIS_CV' => [
                    'orden' => 6,
                    'pruebas' => [
                        ['codigo' => 'CV_ANALISIS', 'peso' => 15.00, 'umbral_origen' => UmbralOrigen::Fijo],
                    ],
                ],
                'ENTREVISTA' => [
                    'orden' => 7,
                    'pruebas' => [
                        ['codigo' => 'ENTREVISTA', 'peso' => 20.00, 'umbral_origen' => UmbralOrigen::Fijo],
                    ],
                ],
                'SELECCION' => [
                    'orden' => 8,
                    'pruebas' => [],
                ],
            ];

            foreach ($etapasConfig as $codigoEtapa => $conf) {
                if (! isset($etapasCatalogo[$codigoEtapa])) {
                    continue;
                }

                $procesoEtapa = $proceso->etapas()->create([
                    'etapa_id' => $etapasCatalogo[$codigoEtapa],
                    'orden' => $conf['orden'],
                    'activa' => true,
                ]);

                foreach ($conf['pruebas'] as $pConf) {
                    $codPrueba = $pConf['codigo'];
                    if (isset($pruebasCatalogo[$codPrueba])) {
                        $procesoEtapa->pruebas()->create([
                            'prueba_id' => $pruebasCatalogo[$codPrueba],
                            'peso' => $pConf['peso'],
                            'umbral_origen' => $pConf['umbral_origen'],
                            'obligatoria' => true,
                        ]);
                    }
                }
            }

            // 4. Configurar Requisitos Documentales Obligatorios
            $requisitosCodigos = ['HISTORICO_ACADEMICO', 'CEDULA_IDENTIDAD', 'FOTOGRAFIA', 'CURRICULUM_VITAE'];
            $tiposDocIds = TipoDocumento::whereIn('codigo', $requisitosCodigos)->pluck('id');
            $proceso->tiposDocumento()->attach($tiposDocIds, ['obligatorio' => true]);

            return response()->json([
                'mensaje' => "Convocatoria '{$proceso->nombre}' creada y aperturada exitosamente.",
                'data' => [
                    'proceso_id' => $proceso->id,
                    'codigo' => $proceso->codigo,
                    'nombre' => $proceso->nombre,
                    'estado' => $proceso->estado->value,
                    'carrera' => $carrera->nombre,
                    'gestion' => $convocatoria->gestion,
                    'numero' => $convocatoria->numero,
                ],
            ], 201);
        });
    }

    /**
     * Actualiza datos de una convocatoria existente (fechas, cupos, estado, título).
     */
    public function update(Request $request, int $procesoId): JsonResponse
    {
        $proceso = Proceso::with('convocatoria.carrera')->findOrFail($procesoId);

        $datos = $request->validate([
            'nombre' => ['sometimes', 'string', 'max:200'],
            'estado' => ['sometimes', Rule::in(['BORRADOR', 'ABIERTO', 'EN_EVALUACION', 'CERRADO', 'CANCELADO'])],
            'top_n' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'fecha_apertura' => ['sometimes', 'date'],
            'fecha_cierre' => ['nullable', 'date', 'after_or_equal:fecha_apertura'],
            'vigencia_perfil_meses' => ['sometimes', 'integer', 'min:1', 'max:60'],
        ]);

        DB::transaction(function () use ($proceso, $datos) {
            if (isset($datos['nombre'])) {
                $proceso->nombre = trim($datos['nombre']);
            }
            if (isset($datos['estado'])) {
                $proceso->estado = EstadoProceso::from($datos['estado']);
            }
            if (isset($datos['top_n'])) {
                $proceso->top_n = (int) $datos['top_n'];
            }
            $proceso->save();

            $conv = $proceso->convocatoria;
            if ($conv) {
                if (isset($datos['fecha_apertura'])) {
                    $conv->fecha_apertura = $datos['fecha_apertura'];
                }
                if (array_key_exists('fecha_cierre', $datos)) {
                    $conv->fecha_cierre = $datos['fecha_cierre'];
                }
                if (isset($datos['vigencia_perfil_meses'])) {
                    $conv->vigencia_perfil_meses = (int) $datos['vigencia_perfil_meses'];
                }
                $conv->save();
            }
        });

        return response()->json([
            'mensaje' => 'Convocatoria actualizada exitosamente.',
            'data' => [
                'proceso_id' => $proceso->id,
                'codigo' => $proceso->codigo,
                'nombre' => $proceso->nombre,
                'estado' => $proceso->estado->value,
                'top_n' => $proceso->top_n,
            ],
        ]);
    }

    /**
     * Elimina o cancela una convocatoria.
     */
    public function destroy(int $procesoId): JsonResponse
    {
        $proceso = Proceso::with(['postulaciones', 'convocatoria'])->findOrFail($procesoId);

        // Si tiene postulaciones registradas, no se debe borrar físicamente; se cancela
        if ($proceso->postulaciones()->count() > 0) {
            $proceso->update(['estado' => EstadoProceso::Cancelado]);

            return response()->json([
                'mensaje' => 'La convocatoria contiene postulaciones registradas de estudiantes. Para preservar la integridad del historial, ha sido marcada como CANCELADA.',
                'data' => ['estado' => 'CANCELADO'],
            ]);
        }

        DB::transaction(function () use ($proceso) {
            // Eliminar dependencias
            $proceso->tiposDocumento()->detach();
            foreach ($proceso->etapas as $etapa) {
                $etapa->pruebas()->delete();
                $etapa->delete();
            }
            $proceso->convocatoria?->delete();
            $proceso->delete();
        });

        return response()->json([
            'mensaje' => 'Convocatoria eliminada exitosamente del sistema.',
        ]);
    }
}
