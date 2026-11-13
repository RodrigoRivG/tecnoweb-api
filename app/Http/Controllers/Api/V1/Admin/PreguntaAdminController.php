<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\TipoPrueba;
use App\Http\Controllers\Controller;
use App\Models\Archivo;
use App\Models\DimensionEvaluada;
use App\Models\OpcionPregunta;
use App\Models\Pregunta;
use App\Models\Prueba;
use App\Models\RespuestaIntento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PreguntaAdminController extends Controller
{
    /**
     * Retorna el catálogo de pruebas psicotécnicas y dimensiones evaluadas.
     * Ordena preferentemente las 4 pruebas nucleares de la evaluación.
     */
    public function catalogoPruebas(): JsonResponse
    {
        $pruebas = Prueba::query()
            ->where('tipo', TipoPrueba::Test)
            ->withCount('preguntas')
            ->orderByRaw("
                CASE codigo
                    WHEN 'TEST_PERSONALIDAD' THEN 1
                    WHEN 'TEST_INTELIGENCIA' THEN 2
                    WHEN 'TEST_INTELIGENCIA_EMOCIONAL' THEN 3
                    WHEN 'TEST_LECTURA_COMPRENSIVA' THEN 4
                    ELSE 5
                END, id ASC
            ")
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
                    'es_nuclear' => in_array($p->codigo, [
                        'TEST_PERSONALIDAD',
                        'TEST_INTELIGENCIA',
                        'TEST_INTELIGENCIA_EMOCIONAL',
                        'TEST_LECTURA_COMPRENSIVA',
                    ], true),
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
     * Lista las preguntas paginadas exclusivamente para una prueba específica.
     */
    public function index(Request $request): JsonResponse
    {
        // Si no se especifica prueba, se selecciona por defecto la primera disponible
        $pruebaId = $request->filled('prueba_id')
            ? $request->integer('prueba_id')
            : Prueba::where('tipo', TipoPrueba::Test)->orderBy('id')->value('id');

        $query = Pregunta::query()
            ->where('prueba_id', $pruebaId)
            ->with([
                'prueba:id,codigo,nombre,activa',
                'dimension:id,codigo,nombre,tipo',
                'opciones',
                'imagen',
            ])
            ->withCount('respuestas');

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

        $perPage = max(5, min(100, $request->integer('per_page', 10)));
        $paginado = $query->orderBy('orden')->orderBy('id')->paginate($perPage);

        return response()->json([
            'data' => $paginado->getCollection()->map(fn (Pregunta $p) => [
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
                'imagen_archivo_id' => $p->imagen_archivo_id,
                'imagen_url' => $p->imagen ? url($p->imagen->clave_almacenamiento) : null,
                'imagen' => $p->imagen ? [
                    'id' => $p->imagen->id,
                    'nombre_original' => $p->imagen->nombre_original,
                    'url' => url($p->imagen->clave_almacenamiento),
                ] : null,
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
            'meta' => [
                'current_page' => $paginado->currentPage(),
                'last_page' => $paginado->lastPage(),
                'per_page' => $paginado->perPage(),
                'total' => $paginado->total(),
                'from' => $paginado->firstItem() ?? 0,
                'to' => $paginado->lastItem() ?? 0,
            ],
        ]);
    }

    /**
     * Sube un archivo de imagen para usarlo en una pregunta o lámina visual.
     */
    public function subirImagen(Request $request): JsonResponse
    {
        $request->validate([
            'imagen' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp,gif,svg', 'max:5120'],
        ], [
            'imagen.required' => 'Debes seleccionar un archivo de imagen.',
            'imagen.image' => 'El archivo seleccionado debe ser una imagen válida.',
            'imagen.max' => 'La imagen no debe exceder los 5MB de tamaño.',
        ]);

        $file = $request->file('imagen');
        $extension = $file->getClientOriginalExtension() ?: 'png';
        $filename = 'pregunta_' . date('Ymd_His') . '_' . Str::random(8) . '.' . $extension;

        $dir = public_path('pruebas/uploads');
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true, true);
        }

        $file->move($dir, $filename);
        $rutaFinal = $dir . DIRECTORY_SEPARATOR . $filename;
        $claveAlmacenamiento = "pruebas/uploads/{$filename}";
        $hash = hash_file('sha256', $rutaFinal);

        $archivo = Archivo::create([
            'nombre_original' => Str::limit($file->getClientOriginalName(), 250, ''),
            'clave_almacenamiento' => $claveAlmacenamiento,
            'mime_type' => $file->getClientMimeType() ?: 'image/png',
            'tamano_bytes' => File::size($rutaFinal),
            'hash_sha256' => $hash,
            'subido_por' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'Imagen subida exitosamente.',
            'data' => [
                'id' => $archivo->id,
                'nombre_original' => $archivo->nombre_original,
                'clave_almacenamiento' => $archivo->clave_almacenamiento,
                'url' => url($archivo->clave_almacenamiento),
            ],
        ], 201);
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
            'imagen',
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
                'imagen_archivo_id' => $pregunta->imagen_archivo_id,
                'imagen_url' => $pregunta->imagen ? url($pregunta->imagen->clave_almacenamiento) : null,
                'imagen' => $pregunta->imagen ? [
                    'id' => $pregunta->imagen->id,
                    'nombre_original' => $pregunta->imagen->nombre_original,
                    'url' => url($pregunta->imagen->clave_almacenamiento),
                ] : null,
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
     * Registra una nueva pregunta con soporte para lámina visual u opciones de respuesta.
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
            'enunciado' => ['nullable', 'string', 'max:2000'],
            'imagen_archivo_id' => ['nullable', 'integer', Rule::exists(Archivo::class, 'id')],
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

        // Asegurar que al menos exista enunciado o imagen
        $enunciadoTexto = trim($validados['enunciado'] ?? '');
        $imagenId = $validados['imagen_archivo_id'] ?? null;
        if (empty($enunciadoTexto) && empty($imagenId)) {
            return response()->json([
                'message' => 'La pregunta debe incluir al menos un enunciado escrito o una imagen ilustrativa.',
            ], 422);
        }

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
                    'message' => "El número de orden {$orden} ya está en uso en esta prueba. Déjelo en blanco para asignación correlativa automática o elija otro número.",
                ], 422);
            }
        }

        $pregunta = DB::transaction(function () use ($validados, $orden, $enunciadoTexto, $imagenId) {
            $preguntaCreada = Pregunta::create([
                'prueba_id' => $validados['prueba_id'],
                'tipo_prueba' => 'TEST',
                'dimension_id' => $validados['dimension_id'] ?? null,
                'orden' => $orden,
                'enunciado' => !empty($enunciadoTexto) ? $enunciadoTexto : null,
                'imagen_archivo_id' => $imagenId,
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

        $pregunta->load(['prueba:id,codigo,nombre,activa', 'dimension:id,codigo,nombre,tipo', 'opciones', 'imagen'])->loadCount('respuestas');

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
                'imagen_archivo_id' => $pregunta->imagen_archivo_id,
                'imagen_url' => $pregunta->imagen ? url($pregunta->imagen->clave_almacenamiento) : null,
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
     * Actualiza una pregunta existente, sus opciones y/o su imagen.
     */
    public function update(Request $request, Pregunta $pregunta): JsonResponse
    {
        $validados = $request->validate([
            'dimension_id' => ['nullable', 'integer', Rule::exists(DimensionEvaluada::class, 'id')],
            'enunciado' => ['nullable', 'string', 'max:2000'],
            'imagen_archivo_id' => ['nullable', 'integer', Rule::exists(Archivo::class, 'id')],
            'orden' => ['nullable', 'integer', 'min:1'],
            'opciones' => ['sometimes', 'array', 'min:2', 'max:10'],
            'opciones.*.id' => ['nullable', 'integer'],
            'opciones.*.etiqueta' => ['required', 'string', 'max:1'],
            'opciones.*.texto' => ['required', 'string', 'max:500'],
            'opciones.*.puntaje' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $enunciadoTexto = isset($validados['enunciado']) ? trim($validados['enunciado']) : $pregunta->enunciado;
        $imagenId = array_key_exists('imagen_archivo_id', $validados) ? $validados['imagen_archivo_id'] : $pregunta->imagen_archivo_id;

        if (empty($enunciadoTexto) && empty($imagenId)) {
            return response()->json([
                'message' => 'La pregunta debe incluir al menos un enunciado escrito o una imagen ilustrativa.',
            ], 422);
        }

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
                        'message' => "No es posible eliminar la opción '{$optToDelete->etiqueta}' ({$optToDelete->texto}) porque ya cuenta con respuestas registradas en evaluaciones de postulantes.",
                    ], 422);
                }
            }
        }

        DB::transaction(function () use ($pregunta, $validados, $nuevoOrden, $enunciadoTexto, $imagenId) {
            $pregunta->update([
                'dimension_id' => $validados['dimension_id'] ?? null,
                'orden' => $nuevoOrden,
                'enunciado' => !empty($enunciadoTexto) ? $enunciadoTexto : null,
                'imagen_archivo_id' => $imagenId,
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

                // Eliminar opciones que ya no están presentes (previamente validadas)
                $pregunta->opciones()->whereNotIn('id', $incomingIds)->delete();
            }
        });

        $pregunta->load(['prueba:id,codigo,nombre,activa', 'dimension:id,codigo,nombre,tipo', 'opciones', 'imagen'])->loadCount('respuestas');

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
                'imagen_archivo_id' => $pregunta->imagen_archivo_id,
                'imagen_url' => $pregunta->imagen ? url($pregunta->imagen->clave_almacenamiento) : null,
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
