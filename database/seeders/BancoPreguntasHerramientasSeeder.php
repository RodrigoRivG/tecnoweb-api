<?php

namespace Database\Seeders;

use App\Models\Archivo;
use App\Models\OpcionPregunta;
use App\Models\Pregunta;
use App\Models\Prueba;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BancoPreguntasHerramientasSeeder extends Seeder
{
    /**
     * Catálogo de 31 preguntas oficiales del Test II de Reconocimiento de Herramientas y Piezas.
     * Fuente: Tests de Aptitud Profesional (J. Ken Klein y Celia Unterman), páginas 194-196.
     * Claves extraídas de la plantilla perforada oficial en Escáner_20261111 (11).png.
     */
    private const PREGUNTAS_OFICIALES = [
        1 => [
            'tarea' => 'Trazar el ángulo de corte en los apoyos laterales de roscas de escaleras.',
            'clave' => 'A',
            'opciones' => [
                'A' => 'Herramienta (A) — Falsa escuadra / transportador de ángulos',
                'B' => 'Herramienta (L) — Gramil de carpintero',
                'C' => 'Herramienta (Y) — Escuadra de carpintero a 90°',
                'D' => 'Herramienta (X) — Nivel de burbuja',
            ],
        ],
        2 => [
            'tarea' => 'Cortar un ladrillo con precisión.',
            'clave' => 'B',
            'opciones' => [
                'A' => 'Herramienta (U) — Maceta de albañil',
                'B' => 'Herramienta (H) — Maza de ladrillero con filo cortador',
                'C' => 'Herramienta (T) — Maza de madera de carpintero',
                'D' => 'Herramienta (G) — Puntero cilíndrico de acero',
            ],
        ],
        3 => [
            'tarea' => 'Marcar líneas paralelas al borde de una tabla.',
            'clave' => 'C',
            'opciones' => [
                'A' => 'Herramienta (R) — Compás de puntas',
                'B' => 'Herramienta (Y) — Escuadra metálica',
                'C' => 'Herramienta (L) — Gramil de carpintero',
                'D' => 'Herramienta (A) — Falsa escuadra',
            ],
        ],
        4 => [
            'tarea' => 'Unir tubos plaqueados de níquel.',
            'clave' => 'A',
            'opciones' => [
                'A' => 'Herramienta (F) — Llave de cadena para tubos',
                'B' => 'Herramienta (S) — Llave Stillson dentada',
                'C' => 'Herramienta (P) — Sargento / prensa en C',
                'D' => 'Herramienta (W) — Prensa paralela de madera',
            ],
        ],
        5 => [
            'tarea' => 'Terminar la superficie plana de un suelo de cemento.',
            'clave' => 'D',
            'opciones' => [
                'A' => 'Herramienta (J) — Paleta triangular de albañil',
                'B' => 'Herramienta (B) — Acanalador para juntas',
                'C' => 'Herramienta (K) — Biselador curvo de bordes',
                'D' => 'Herramienta (Q) — Llana plana de albañil',
            ],
        ],
        6 => [
            'tarea' => 'Comprobar que el lado de una forma de hormigón es vertical.',
            'clave' => 'B',
            'opciones' => [
                'A' => 'Herramienta (Y) — Escuadra a 90°',
                'B' => 'Herramienta (X) — Nivel de burbuja',
                'C' => 'Herramienta (L) — Gramil de carpintero',
                'D' => 'Herramienta (A) — Falsa escuadra',
            ],
        ],
        7 => [
            'tarea' => 'Taladrar un agujero de 1,89 cm. en un canal de hierro de 15,24 cm.',
            'clave' => 'A',
            'opciones' => [
                'A' => 'Herramienta (V) — Taladro de columna / taladro de banco',
                'B' => 'Herramienta (O) — Taladro manual de engranajes',
                'C' => 'Herramienta (Z) — Berbiquí manual de carpintería',
                'D' => 'Herramienta (C) — Tajadera de yunque',
            ],
        ],
        8 => [
            'tarea' => 'Localizar el centro de una placa circular que tiene un diámetro de 30,48 cm.',
            'clave' => 'C',
            'opciones' => [
                'A' => 'Herramienta (X) — Nivel de burbuja',
                'B' => 'Herramienta (L) — Gramil de carpintero',
                'C' => 'Herramienta (A) — Escuadra / transportador de centrar',
                'D' => 'Herramienta (W) — Prensa de madera',
            ],
        ],
        9 => [
            'tarea' => 'Forjar un aro de hierro.',
            'clave' => 'B',
            'opciones' => [
                'A' => 'Herramienta (C) — Tajadera de banco',
                'B' => 'Herramienta (E) — Yunque de herrero',
                'C' => 'Herramienta (M) — Desbastador cilíndrico',
                'D' => 'Herramienta (U) — Maceta de albañil',
            ],
        ],
        10 => [
            'tarea' => 'Doblar una varilla de 1,27 cm. para un soporte en U para un tubo de 12,54 centímetros.',
            'clave' => 'A',
            'opciones' => [
                'A' => 'Herramienta (E) — Cuerno cónico del yunque',
                'B' => 'Herramienta (P) — Sargento en C',
                'C' => 'Herramienta (W) — Prensa paralela',
                'D' => 'Herramienta (T) — Mazo de madera',
            ],
        ],
        11 => [
            'tarea' => 'Unir un tubo de hierro a un pasamanos.',
            'clave' => 'C',
            'opciones' => [
                'A' => 'Herramienta (F) — Llave de cadena',
                'B' => 'Herramienta (P) — Sargento en C',
                'C' => 'Herramienta (S) — Llave Stillson para tubos',
                'D' => 'Herramienta (T) — Mazo de carpintero',
            ],
        ],
        12 => [
            'tarea' => 'Biselar un borde curvo de cemento.',
            'clave' => 'B',
            'opciones' => [
                'A' => 'Herramienta (Q) — Llana plana',
                'B' => 'Herramienta (K) — Canteador / biselador curvo de cemento',
                'C' => 'Herramienta (B) — Acanalador de juntas',
                'D' => 'Herramienta (J) — Paleta de albañil',
            ],
        ],
        13 => [
            'tarea' => 'Rellenar las junturas de una mampostería vieja.',
            'clave' => 'D',
            'opciones' => [
                'A' => 'Herramienta (J) — Paleta de albañil',
                'B' => 'Herramienta (Q) — Llana plana',
                'C' => 'Herramienta (B) — Acanalador de bloques',
                'D' => 'Herramienta (N) — Palustre pequeño para juntas',
            ],
        ],
        14 => [
            'tarea' => 'Poner un ladrillo vidriado en una pared.',
            'clave' => 'A',
            'opciones' => [
                'A' => 'Herramienta (J) — Paleta triangular de albañil',
                'B' => 'Herramienta (Q) — Llana plana',
                'C' => 'Herramienta (N) — Palustre de juntas',
                'D' => 'Herramienta (H) — Maza de ladrillero',
            ],
        ],
        15 => [
            'tarea' => 'Taladrar un agujero en hormigón para un tubo de 2,54 cm.',
            'clave' => 'C',
            'opciones' => [
                'A' => 'Herramienta (V) — Taladro de columna',
                'B' => 'Herramienta (O) — Taladro manual',
                'C' => 'Herramienta (U) — Maceta de albañil con puntero',
                'D' => 'Herramienta (T) — Mazo de madera',
            ],
        ],
        16 => [
            'tarea' => 'Acanalar un suelo de cemento para formar bloques.',
            'clave' => 'B',
            'opciones' => [
                'A' => 'Herramienta (K) — Biselador de bordes',
                'B' => 'Herramienta (B) — Acanalador / llaguero para juntas de suelo',
                'C' => 'Herramienta (Q) — Llana plana',
                'D' => 'Herramienta (N) — Palustre de juntas',
            ],
        ],
        17 => [
            'tarea' => 'Trazar una línea precisa para cortar con una sierra a 90° con respecto al borde de una tabla.',
            'clave' => 'A',
            'opciones' => [
                'A' => 'Herramienta (Y) — Escuadra de carpintero a 90°',
                'B' => 'Herramienta (A) — Falsa escuadra móvil',
                'C' => 'Herramienta (L) — Gramil de carpintero',
                'D' => 'Herramienta (X) — Nivel de burbuja',
            ],
        ],
        18 => [
            'tarea' => 'Sujetar chapa metálica para soldar.',
            'clave' => 'C',
            'opciones' => [
                'A' => 'Herramienta (W) — Prensa paralela de madera',
                'B' => 'Herramienta (F) — Llave de tubos',
                'C' => 'Herramienta (P) — Sargento / prensa en C',
                'D' => 'Herramienta (S) — Llave Stillson',
            ],
        ],
        19 => [
            'tarea' => 'Localizar un punto para un agujero en el suelo directamente debajo de un punto del techo.',
            'clave' => 'B',
            'opciones' => [
                'A' => 'Herramienta (X) — Nivel de burbuja',
                'B' => 'Herramienta (D) — Plomada de albañil',
                'C' => 'Herramienta (Y) — Escuadra a 90°',
                'D' => 'Herramienta (R) — Compás de puntas',
            ],
        ],
        20 => [
            'tarea' => 'Cortar un canal en hormigón para un tubo con ayuda de la herramienta U (maceta).',
            'clave' => 'D',
            'opciones' => [
                'A' => 'Herramienta (C) — Tajadera de yunque',
                'B' => 'Herramienta (H) — Maza de ladrillero',
                'C' => 'Herramienta (N) — Palustre de mampostería',
                'D' => 'Herramienta (G) — Puntero de acero templado',
            ],
        ],
        21 => [
            'tarea' => 'Marcar una serie de pequeñas distancias iguales.',
            'clave' => 'A',
            'opciones' => [
                'A' => 'Herramienta (R) — Compás de puntas / compás divisor',
                'B' => 'Herramienta (L) — Gramil de carpintero',
                'C' => 'Herramienta (Y) — Escuadra milimetrada',
                'D' => 'Herramienta (A) — Falsa escuadra',
            ],
        ],
        22 => [
            'tarea' => 'Trazar una línea horizontal en el centro de una pared.',
            'clave' => 'B',
            'opciones' => [
                'A' => 'Herramienta (D) — Plomada',
                'B' => 'Herramienta (X) — Nivel de burbuja',
                'C' => 'Herramienta (Y) — Escuadra de carpintero',
                'D' => 'Herramienta (R) — Compás de trazar',
            ],
        ],
        23 => [
            'tarea' => 'Manejar una gubia de carpintero.',
            'clave' => 'C',
            'opciones' => [
                'A' => 'Herramienta (U) — Maceta pesada de metal',
                'B' => 'Herramienta (H) — Maza de albañil',
                'C' => 'Herramienta (T) — Maza de madera de carpintero',
                'D' => 'Herramienta (P) — Sargento en C',
            ],
        ],
        24 => [
            'tarea' => 'Manejar un taladro para hacer un agujero de 0,63 cm. en una columna.',
            'clave' => 'A',
            'opciones' => [
                'A' => 'Herramienta (C) — Tajadera / broca de banco',
                'B' => 'Herramienta (G) — Puntero cilíndrico',
                'C' => 'Herramienta (T) — Maza de madera',
                'D' => 'Herramienta (M) — Desbastador',
            ],
        ],
        25 => [
            'tarea' => 'Hacer agujeros en madera con gusanillo de rosca.',
            'clave' => 'D',
            'opciones' => [
                'A' => 'Herramienta (O) — Taladro manual de engranajes',
                'B' => 'Herramienta (V) — Taladro de columna',
                'C' => 'Herramienta (T) — Maza de madera',
                'D' => 'Herramienta (Z) — Berbiquí manual de carpintería',
            ],
        ],
        26 => [
            'tarea' => 'Manejar un destornillador de berbiquí.',
            'clave' => 'B',
            'opciones' => [
                'A' => 'Herramienta (O) — Taladro manual',
                'B' => 'Herramienta (Z) — Berbiquí manual con mandril para puntas',
                'C' => 'Herramienta (P) — Prensa en C',
                'D' => 'Herramienta (W) — Prensa de madera',
            ],
        ],
        27 => [
            'tarea' => 'Trazar una línea de tiza.',
            'clave' => 'A',
            'opciones' => [
                'A' => 'Herramienta (D) — Plomada con cuerda de tiza',
                'B' => 'Herramienta (X) — Nivel de burbuja',
                'C' => 'Herramienta (Y) — Escuadra',
                'D' => 'Herramienta (L) — Gramil',
            ],
        ],
        28 => [
            'tarea' => 'Unir una juntura con cola de milano.',
            'clave' => 'C',
            'opciones' => [
                'A' => 'Herramienta (U) — Maceta pesada de albañil',
                'B' => 'Herramienta (P) — Sargento en C',
                'C' => 'Herramienta (T) — Mazo de madera de carpintero',
                'D' => 'Herramienta (H) — Maza de ladrillero',
            ],
        ],
        29 => [
            'tarea' => 'Doblar una chapa de cobre delgada en forma de cilindro.',
            'clave' => 'D',
            'opciones' => [
                'A' => 'Herramienta (E) — Yunque plano',
                'B' => 'Herramienta (W) — Prensa paralela',
                'C' => 'Herramienta (C) — Tajadera de corte',
                'D' => 'Herramienta (M) — Desbastador / conformador cilíndrico',
            ],
        ],
        30 => [
            'tarea' => 'Marcar una esquina de tabla para redondearla.',
            'clave' => 'A',
            'opciones' => [
                'A' => 'Herramienta (R) — Compás de puntas / trazar',
                'B' => 'Herramienta (Y) — Escuadra recta a 90°',
                'C' => 'Herramienta (A) — Falsa escuadra',
                'D' => 'Herramienta (L) — Gramil de carpintero',
            ],
        ],
        31 => [
            'tarea' => 'Aplanar el extremo de un tubo de hierro de 2,54 cm. sobre una placa de acero.',
            'clave' => 'B',
            'opciones' => [
                'A' => 'Herramienta (T) — Mazo de madera',
                'B' => 'Herramienta (U) — Maceta pesada de albañil / forja',
                'C' => 'Herramienta (H) — Maza con filo',
                'D' => 'Herramienta (M) — Desbastador',
            ],
        ],
    ];

    public function run(): void
    {
        $prueba = Prueba::query()->where('codigo', 'TEST_HERRAMIENTAS')->firstOrFail();

        $this->command?->info("Poblando banco de preguntas para la prueba [{$prueba->codigo}] (ID: {$prueba->id})...");

        DB::transaction(function () use ($prueba) {
            // 1. Limpiar respuestas de intentos previos referidas a estas preguntas
            $preguntasIds = Pregunta::query()->where('prueba_id', $prueba->id)->pluck('id');
            DB::table('talento.respuesta_intento')->whereIn('pregunta_id', $preguntasIds)->delete();

            // 2. Limpiar preguntas previas de esta prueba (en cascada limpia opciones)
            Pregunta::query()->where('prueba_id', $prueba->id)->delete();

            // 3. Registrar el archivo de la lámina completa de referencia de herramientas
            $rutaFisica = public_path('pruebas/herramientas/lamina_completa.png');
            $claveAlmacenamiento = 'pruebas/herramientas/lamina_completa.png';
            $archivoId = null;

            if (File::exists($rutaFisica)) {
                $tamano = File::size($rutaFisica);
                $hash = hash_file('sha256', $rutaFisica);

                $archivo = Archivo::query()->updateOrCreate(
                    ['clave_almacenamiento' => $claveAlmacenamiento],
                    [
                        'nombre_original' => 'lamina_herramientas.png',
                        'mime_type' => 'image/png',
                        'tamano_bytes' => $tamano,
                        'hash_sha256' => $hash,
                        'subido_por' => 1,
                    ]
                );
                $archivoId = $archivo->id;
            }

            // 4. Registrar cada una de las 31 preguntas oficiales
            foreach (self::PREGUNTAS_OFICIALES as $nro => $item) {
                $enunciado = "Tarea técnica #{$nro}:\n«{$item['tarea']}»\n\nIdentifique cuál de las siguientes herramientas o piezas de trabajo de la lámina oficial se requiere normalmente para ejecutar esta labor.";

                $pregunta = Pregunta::query()->create([
                    'prueba_id' => $prueba->id,
                    'tipo_prueba' => 'TEST',
                    'dimension_id' => null,
                    'orden' => $nro,
                    'enunciado' => $enunciado,
                    'imagen_archivo_id' => $archivoId,
                ]);

                // 5. Crear las 4 opciones de respuesta (A, B, C, D)
                $opcionesData = [];
                foreach ($item['opciones'] as $letra => $texto) {
                    $esCorrecta = ($letra === $item['clave']);
                    $opcionesData[] = [
                        'pregunta_id' => $pregunta->id,
                        'etiqueta' => $letra,
                        'texto' => $texto,
                        'puntaje' => $esCorrecta ? '1.00' : '0.00',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                OpcionPregunta::query()->insert($opcionesData);
            }
        });

        $total = Pregunta::query()->where('prueba_id', $prueba->id)->count();
        $this->command?->info("¡Banco de preguntas de Reconocimiento de Herramientas cargado exitosamente! Total de ítems registrados: {$total}.");
    }
}

