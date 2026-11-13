<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\TipoPrueba;
use App\Http\Controllers\Controller;
use App\Models\DimensionEvaluada;
use App\Models\OpcionPregunta;
use App\Models\Pregunta;
use App\Models\Prueba;
use App\Models\RespuestaIntento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PreguntaAdminController extends Controller
{
    /**
     * Retorna el catálogo de pruebas psicotécnicas y dimensiones evaluadas,
     * junto con estadísticas globales para la cabecera del módulo.
     */
    public function catalogoPruebas(): JsonResponse
    {
        $pruebas = Prueba::query()
            ->where('tipo', TipoPrueba::Test)
            ->withCount('preguntas')
            ->orderBy('id')
            ->get();

        $dimensiones = DimensionEvaluada::query()
            ->orderBy('tipo')
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        $totalPreguntas = Pregunta::count();

        return response()->json([
            'data' => [
                'pruebas' => $pruebas->map(fn (Prueba $p) => [
                    'id' => $p->id,
                    'codigo' => $p->codigo,
                    'nombre' => $p->nombre,
                    'descripcion' => $p->descripcion,
                    'activa' => $p->activa,
                    'preguntas_count' => $p->preguntas_count,
                ]),
                'dimensiones' => $dimensiones->map(fn (DimensionEvaluada $d) => [
                    'id' => $d->id,
                    'codigo' => $d->codigo,
                    'nombre' => $d->nombre,
                    'tipo' => $d->tipo,
                    'orden' => $d->orden,
                ]),
                'resumen' => [
                    'total_pruebas' => $pruebas->count(),
                    'total_preguntas' => $totalPreguntas,
                    'total_dimensiones' => $dimensiones->count(),
                ],
            ],
        ]);
    }

    /**
     * Lista las preguntas con soporte para filtros por prueba, dimensión y búsqueda.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Pregunta::query()
            ->with([
                'prueba:id,codigo,nombre,activa',
                'dimension:id,codigo,nombre,tipo',
                'opciones',
            ])
            ->withCount('respuestas');

        if ($request->filled('prueba_id')) {
            $query->where('prueba_id', $request->integer('prueba_id'));
        }

        if ($request->filled('dimension_id')) {
            $query->where('dimension_id', $request->integer('dimension_id'));
        }

        if ($request->filled('q')) {
            $termino = '%' . trim($request->string('q')) . '%';
            $query->where(function ($q) use ($termino) {
                $q->where('enunciado', 'ilike', $termino)
                    ->orWhereHas('opciones', fn ($qo) => $qo->where('texto', 'ilike', $termino));
            });
        }

        $preguntas = $query->orderBy('orden')->orderBy('id')->get();

        return response()->json([
            'data' => $preguntas->map(fn (Pregunta $p) => [
                'id' => $p->id,
                'prueba_id' => $p->prueba_id,
                'prueba' => $p->prueba ? [
                    'id' => $p->prueba->id,
                    'codigo' => $p->prueba->codigo,
                    'nombre' => $p->prueba->nombre,
                    'activa' => $p->prueba->activa,
                ] : null,
                'dimension_id' => $p->dimension_id,
                'dimension' => $p->dimension ? [
                    'id' => $p->dimension->id,
                    'codigo' => $p->dimension->codigo,
                    'nombre' => $p->dimension->nombre,
                    'tipo' => $p->dimension->tipo,
                ] : null,
                'orden' => $p->orden,
                'enunciado' => $p->enunciado,
                'tiene_respuestas' => $p->respuestas_count > 0,
                'respuestas_count' => $p->respuestas_count,
                'opciones' => $p->opciones->map(fn (OpcionPregunta $o) => [
                    'id' => $o->id,
                    'etiqueta' => $o->etiqueta,
                    'texto' => $o->texto,
                    'puntaje' => (float) $o->puntaje,
                ]),
                'created_at' => $p->created_at?->toIso8601String(),
                'updated_at' => $p->updated_at?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Muestra el detalle de una pregunta específica.
     */
    public function show(Pregunta $pregunta): JsonResponse
    {
        $pregunta->load([
            'prueba:id,codigo,nombre,activa',
            'dimension:id,codigo,nombre,tipo',
            'opciones',
        ])->loadCount('respuestas');

        return response()->json([
            'data' => [
                'id' => $pregunta->id,
                'prueba_id' => $pregunta->prueba_id,
                'prueba' => $pregunta->prueba ? [
                    'id' => $pregunta->prueba->id,
                    'codigo' => $pregunta->prueba->codigo,
                    'nombre' => $pregunta->prueba->nombre,
                    'activa' => $pregunta->prueba->activa,
                ] : null,
                'dimension_id' => $pregunta->dimension_id,
                'dimension' => $pregunta->dimension ? [
                    'id' => $pregunta->dimension->id,
                    'codigo' => $pregunta->dimension->codigo,
                    'nombre' => $pregunta->dimension->nombre,
                    'tipo' => $pregunta->dimension->tipo,
                ] : null,
                'orden' => $pregunta->orden,
                'enunciado' => $pregunta->enunciado,
                'tiene_respuestas' => $pregunta->respuestas_count > 0,
                'respuestas_count' => $pregunta->respuestas_count,
                'opciones' => $pregunta->opciones->map(fn (OpcionPregunta $o) => [
                    'id' => $o->id,
                    'etiqueta' => $o->etiqueta,
                    'texto' => $o->texto,
                    'puntaje' => (float) $o->puntaje,
                ]),
                'created_at' => $pregunta->created_at?->toIso8601String(),
                'updated_at' => $pregunta->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Registra una nueva pregunta con sus opciones de respuesta y puntajes.
     */
    public function store(Request $request): JsonResponse
    {
        $validados = $request->validate([
            'prueba_id' => [
                'required',
                'integer',
                Rule::exists(Prueba::class, 'id')->where('tipo', TipoPrueba::Test->value),
            ],
            'dimension_id' => ['nullable', 'integer', Rule::exists(DimensionEvaluada::class, 'id')],
            'enunciado' => ['required', 'string', 'min:3', 'max:2000'],
            'orden' => ['nullable', 'integer', 'min:1'],
            'opciones' => ['required', 'array', 'min:2', 'max:10'],
            'opciones.*.etiqueta' => ['required', 'string', 'max:1'],
            'opciones.*.texto' => ['required', 'string', 'max:500'],
            'opciones.*.puntaje' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'prueba_id.exists' => 'La prueba seleccionada no existe o no admite banco de preguntas (debe ser de tipo TEST).',
            'opciones.min' => 'La pregunta debe incluir al menos 2 opciones de respuesta.',
            'opciones.*.etiqueta.required' => 'Cada opción requiere una etiqueta (ej. A, B, C, 1, 2).',
            'opciones.*.texto.required' => 'El texto de la opción no puede estar vacío.',
        ]);

        // Verificar que no existan etiquetas duplicadas en la lista de opciones
        $etiquetas = array_map(fn ($o) => strtoupper(trim((string) $o['etiqueta'])), $validados['opciones']);
        if (count($etiquetas) !== count(array_unique($etiquetas))) {
            return response()->json([
                'message' => 'No se permiten etiquetas duplicadas entre las opciones de una misma pregunta.',
            ], 422);
        }

        // Determinar orden correlativo
        $orden = $validados['orden'] ?? null;
        if (!$orden || $orden < 1) {
            $orden = ((int) Pregunta::where('prueba_id', $validados['prueba_id'])->max('orden')) + 1;
        } else {
            $ordenExistente = Pregunta::where('prueba_id', $validados['prueba_id'])
                ->where('orden', $orden)
                ->exists();
            if ($ordenExistente) {
                return response()->json([
                    'message' => "El número de orden {$orden} ya está en uso en esta prueba. Déjelo en blanco para asignación automática o elija otro número.",
                ], 422);
            }
        }

        $pregunta = DB::transaction(function () use ($validados, $orden) {
            $preguntaCreada = Pregunta::create([
                'prueba_id' => $validados['prueba_id'],
                'tipo_prueba' => 'TEST',
                'dimension_id' => $validados['dimension_id'] ?? null,
                'orden' => $orden,
                'enunciado' => trim($validados['enunciado']),
            ]);

            foreach ($validados['opciones'] as $opcion) {
                $preguntaCreada->opciones()->create([
                    'etiqueta' => strtoupper(trim((string) $opcion['etiqueta'])),
                    'texto' => trim((string) $opcion['texto']),
                    'puntaje' => (float) $opcion['puntaje'],
                ]);
            }

            return $preguntaCreada;
        });

        $pregunta->load(['prueba:id,codigo,nombre,activa', 'dimension:id,codigo,nombre,tipo', 'opciones'])->loadCount('respuestas');

        return response()->json([
            'message' => 'Pregunta registrada exitosamente en el banco.',
            'data' => [
                'id' => $pregunta->id,
                'prueba_id' => $pregunta->prueba_id,
                'prueba' => $pregunta->prueba,
                'dimension_id' => $pregunta->dimension_id,
                'dimension' => $pregunta->dimension,
                'orden' => $pregunta->orden,
                'enunciado' => $pregunta->enunciado,
                'tiene_respuestas' => false,
                'respuestas_count' => 0,
                'opciones' => $pregunta->opciones->map(fn (OpcionPregunta $o) => [
                    'id' => $o->id,
                    'etiqueta' => $o->etiqueta,
                    'texto' => $o->texto,
                    'puntaje' => (float) $o->puntaje,
                ]),
                'created_at' => $pregunta->created_at?->toIso8601String(),
                'updated_at' => $pregunta->updated_at?->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Actualiza una pregunta existente y sus opciones de respuesta.
     */
    public function update(Request $request, Pregunta $pregunta): JsonResponse
    {
        $validados = $request->validate([
            'dimension_id' => ['nullable', 'integer', Rule::exists(DimensionEvaluada::class, 'id')],
            'enunciado' => ['required', 'string', 'min:3', 'max:2000'],
            'orden' => ['nullable', 'integer', 'min:1'],
            'opciones' => ['sometimes', 'array', 'min:2', 'max:10'],
            'opciones.*.id' => ['nullable', 'integer'],
            'opciones.*.etiqueta' => ['required', 'string', 'max:1'],
            'opciones.*.texto' => ['required', 'string', 'max:500'],
            'opciones.*.puntaje' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        // Validar orden único si ha cambiado
        $nuevoOrden = $validados['orden'] ?? $pregunta->orden;
        if ($nuevoOrden !== $pregunta->orden) {
            $ordenOcupado = Pregunta::where('prueba_id', $pregunta->prueba_id)
                ->where('orden', $nuevoOrden)
                ->where('id', '!=', $pregunta->id)
                ->exists();
            if ($ordenOcupado) {
                return response()->json([
                    'message' => "El número de orden {$nuevoOrden} ya está asignado a otra pregunta de esta prueba.",
                ], 422);
            }
        }

        // Si se proveen opciones, verificar duplicados de etiquetas
        if (isset($validados['opciones'])) {
            $etiquetas = array_map(fn ($o) => strtoupper(trim((string) $o['etiqueta'])), $validados['opciones']);
            if (count($etiquetas) !== count(array_unique($etiquetas))) {
                return response()->json([
                    'message' => 'No se permiten etiquetas duplicadas entre las opciones de una misma pregunta.',
                ], 422);
            }

            // Validar que no se intenten eliminar opciones con respuestas registradas
            $incomingIds = collect($validados['opciones'])->pluck('id')->filter()->all();
            $existingOptions = $pregunta->opciones;
            $optionsToDelete = $existingOptions->filter(fn ($o) => !in_array($o->id, $incomingIds));

            foreach ($optionsToDelete as $optToDelete) {
                if (RespuestaIntento::where('opcion_id', $optToDelete->id)->exists()) {
                    return response()->json([
                        'message' => "No es posible eliminar la opción '{$optToDelete->etiqueta}' ({$optToDelete->texto}) porque ya cuenta con respuestas de postulantes registradas en evaluaciones.",
                    ], 422);
                }
            }
        }

        DB::transaction(function () use ($pregunta, $validados, $nuevoOrden) {
            $pregunta->update([
                'dimension_id' => $validados['dimension_id'] ?? null,
                'orden' => $nuevoOrden,
                'enunciado' => trim($validados['enunciado']),
            ]);

            if (isset($validados['opciones'])) {
                $incomingIds = [];

                foreach ($validados['opciones'] as $opcionData) {
                    $etiqueta = strtoupper(trim((string) $opcionData['etiqueta']));
                    $texto = trim((string) $opcionData['texto']);
                    $puntaje = (float) $opcionData['puntaje'];

                    if (!empty($opcionData['id'])) {
                        $opcionExistente = OpcionPregunta::where('pregunta_id', $pregunta->id)
                            ->where('id', $opcionData['id'])
                            ->first();

                        if ($opcionExistente) {
                            $opcionExistente->update([
                                'etiqueta' => $etiqueta,
                                'texto' => $texto,
                                'puntaje' => $puntaje,
                            ]);
                            $incomingIds[] = $opcionExistente->id;
                            continue;
                        }
                    }

                    // Nueva opción
                    $nueva = $pregunta->opciones()->create([
                        'etiqueta' => $etiqueta,
                        'texto' => $texto,
                        'puntaje' => $puntaje,
                    ]);
                    $incomingIds[] = $nueva->id;
                }

                // Eliminar opciones que ya no están presentes (previamente validadas de que no tienen respuestas)
                $pregunta->opciones()->whereNotIn('id', $incomingIds)->delete();
            }
        });

        $pregunta->load(['prueba:id,codigo,nombre,activa', 'dimension:id,codigo,nombre,tipo', 'opciones'])->loadCount('respuestas');

        return response()->json([
            'message' => 'Pregunta actualizada correctamente.',
            'data' => [
                'id' => $pregunta->id,
                'prueba_id' => $pregunta->prueba_id,
                'prueba' => $pregunta->prueba,
                'dimension_id' => $pregunta->dimension_id,
                'dimension' => $pregunta->dimension,
                'orden' => $pregunta->orden,
                'enunciado' => $pregunta->enunciado,
                'tiene_respuestas' => $pregunta->respuestas_count > 0,
                'respuestas_count' => $pregunta->respuestas_count,
                'opciones' => $pregunta->opciones->map(fn (OpcionPregunta $o) => [
                    'id' => $o->id,
                    'etiqueta' => $o->etiqueta,
                    'texto' => $o->texto,
                    'puntaje' => (float) $o->puntaje,
                ]),
                'created_at' => $pregunta->created_at?->toIso8601String(),
                'updated_at' => $pregunta->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Elimina una pregunta si no registra respuestas de postulantes.
     */
    public function destroy(Pregunta $pregunta): JsonResponse
    {
        if ($pregunta->respuestas()->exists()) {
            return response()->json([
                'message' => 'No es posible eliminar esta pregunta porque ya registra respuestas de postulantes en evaluaciones previas.',
            ], 422);
        }

        DB::transaction(function () use ($pregunta) {
            $pregunta->opciones()->delete();
            $pregunta->delete();
        });

        return response()->json([
            'message' => 'Pregunta y sus opciones eliminadas exitosamente.',
        ]);
    }
}
