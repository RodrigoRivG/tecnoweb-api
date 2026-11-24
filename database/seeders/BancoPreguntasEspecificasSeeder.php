<?php

namespace Database\Seeders;

use App\Models\Pregunta;
use App\Models\Prueba;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Bancos de aptitud mecánica, codificación y laberintos visoespaciales.
 */
class BancoPreguntasEspecificasSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::libros() as $codigo => $items) {
            $prueba = Prueba::query()->where('codigo', $codigo)->first();
            if ($prueba === null) {
                continue;
            }

            DB::transaction(function () use ($prueba, $items): void {
                $ids = Pregunta::query()->where('prueba_id', $prueba->id)->pluck('id');
                if ($ids->isNotEmpty()) {
                    DB::table('talento.respuesta_intento')->whereIn('pregunta_id', $ids)->delete();
                    Pregunta::query()->where('prueba_id', $prueba->id)->delete();
                }

                foreach ($items as $orden => $item) {
                    $pregunta = Pregunta::query()->create([
                        'prueba_id' => $prueba->id,
                        'tipo_prueba' => 'TEST',
                        'orden' => $orden + 1,
                        'enunciado' => $item['enunciado'],
                    ]);

                    foreach ($item['opciones'] as $opcion) {
                        $pregunta->opciones()->create([
                            'etiqueta' => $opcion['etiqueta'],
                            'texto' => $opcion['texto'],
                            'puntaje' => $opcion['puntaje'],
                        ]);
                    }
                }
            });

            $this->command?->info("{$codigo}: ".count($items).' preguntas.');
        }
    }

    /**
     * @return array<string, list<array{enunciado: string, opciones: list<array{etiqueta: string, texto: string, puntaje: int}>}>>
     */
    private static function libros(): array
    {
        return [
            'TEST_APTITUD_MECANICA' => [
                self::item('Una palanca tiene el apoyo en el centro. Si empujas el extremo izquierdo hacia abajo, el extremo derecho:', [
                    ['Baja', 0], ['Sube', 1], ['No se mueve', 0], ['Gira sobre sí mismo', 0],
                ]),
                self::item('Dos engranajes están en contacto. Si el de la izquierda gira en sentido horario, el de la derecha gira:', [
                    ['En sentido horario', 0], ['En sentido antihorario', 1], ['Se detiene', 0], ['En el mismo sentido solo si es más grande', 0],
                ]),
                self::item('Un engranaje de 12 dientes mueve a uno de 24. Por cada vuelta del pequeño, el grande da:', [
                    ['Dos vueltas', 0], ['Media vuelta', 1], ['Una vuelta', 0], ['Cuatro vueltas', 0],
                ]),
                self::item('En una polea fija, para subir la carga hay que tirar la cuerda:', [
                    ['Hacia abajo, y la carga sube la misma distancia', 1], ['Hacia arriba', 0], ['Hacia el costado, y la carga no se mueve', 0], ['Con el doble de fuerza y la mitad de recorrido', 0],
                ]),
                self::item('Una polea móvil reduce el esfuerzo a la mitad. Para subir la carga 1 metro, la cuerda que tiras recorre:', [
                    ['1 metro', 0], ['2 metros', 1], ['0,5 metros', 0], ['No hace falta tirar cuerda', 0],
                ]),
                self::item('Un plano inclinado más largo y menos empinado, para la misma altura, exige:', [
                    ['Más fuerza y menos recorrido', 0], ['Menos fuerza y más recorrido', 1], ['La misma fuerza y el mismo recorrido', 0], ['Más fuerza y más recorrido', 0],
                ]),
                self::item('Tres tuercas iguales están en un mismo perno. Si giras la primera en sentido horario y las otras no se traban, las tres:', [
                    ['Avanzan hacia la cabeza del perno si la rosca es la habitual a derecha', 1], ['Se salen del perno', 0], ['Giran sin desplazarse', 0], ['Avanzan en sentidos opuestos', 0],
                ]),
                self::item('Una rueda y su eje giran juntos. Un punto en el borde de la rueda, comparado con un punto del eje, recorre:', [
                    ['Menos distancia por vuelta', 0], ['La misma distancia', 0], ['Más distancia por vuelta', 1], ['Distancia cero', 0],
                ]),
                self::item('Dos vasos comunicados tienen el mismo líquido en reposo. El nivel queda:', [
                    ['Más alto en el vaso más ancho', 0], ['Más alto en el vaso más angosto', 0], ['A la misma altura', 1], ['Según el color del vaso', 0],
                ]),
                self::item('Empujas una caja sobre el piso. Si el piso pasa de rugoso a liso, la fuerza que necesitas para mantenerla en movimiento:', [
                    ['Aumenta', 0], ['Disminuye', 1], ['Se duplica siempre', 0], ['Depende solo del color de la caja', 0],
                ]),
                self::item('Una balanza de brazos iguales está en equilibrio. Pasas una pesa del platillo izquierdo al derecho. El lado derecho:', [
                    ['Sube', 0], ['Baja', 1], ['Queda igual', 0], ['Oscila y vuelve al centro', 0],
                ]),
                self::item('Un resorte sostiene un peso. Si cuelgas el doble de peso y el resorte no llega a su tope, la elongación:', [
                    ['Se reduce a la mitad', 0], ['Se mantiene', 0], ['Aumenta, cerca del doble si el resorte es elástico', 1], ['Desaparece', 0],
                ]),
            ],
            'TEST_CODIFICACION' => [
                self::item('La instrucción dice: si el número es par, escribe A; si es impar, escribe B. Para 8 corresponde:', [
                    ['A', 1], ['B', 0], ['AB', 0], ['Nada', 0],
                ]),
                self::item('Código: 1 = norte, 2 = este, 3 = sur, 4 = oeste. La secuencia 1, 2, 3 termina mirando al:', [
                    ['Norte', 0], ['Este', 0], ['Sur', 1], ['Oeste', 0],
                ]),
                self::item('Regla: cada letra avanza un lugar en el abecedario. CASA se convierte en:', [
                    ['DBTB', 1], ['BZRZ', 0], ['CASA', 0], ['DBSA', 0],
                ]),
                self::item('Si lees de derecha a izquierda y omites las vocales, de «DATO» queda:', [
                    ['TD', 1], ['DT', 0], ['OTAD', 0], ['A', 0],
                ]),
                self::item('Un contador empieza en 0. Sumas 3, restas 1 y sumas 3. El valor final es:', [
                    ['4', 0], ['5', 1], ['6', 0], ['7', 0],
                ]),
                self::item('La clave cambia el orden: primero la última letra, luego la primera, luego el resto. De «MESA» resulta:', [
                    ['AMES', 1], ['ASEM', 0], ['SEAM', 0], ['MESA', 0],
                ]),
                self::item('Marca con 1 si las dos palabras riman y con 0 si no. «Peso» y «queso» reciben:', [
                    ['0', 0], ['1', 1], ['2', 0], ['No se puede decidir', 0],
                ]),
                self::item('Sustituye cada dígito por su paridad: P par, I impar. 2047 queda:', [
                    ['PIPI', 0], ['PPII', 1], ['IPPI', 0], ['IIPP', 0],
                ]),
                self::item('La regla borra la letra del medio. De «PLANO» queda:', [
                    ['PLNO', 1], ['PANO', 0], ['PLAO', 0], ['LANO', 0],
                ]),
                self::item('Si A = 1, B = 2 y C = 3, la suma de «CAB» es:', [
                    ['5', 0], ['6', 1], ['7', 0], ['4', 0],
                ]),
                self::item('Repites el símbolo solo cuando el anterior fue un círculo. La serie ○ ■ ○ produce como siguiente marca, si la regla pide copiar tras círculo:', [
                    ['○', 1], ['■', 0], ['△', 0], ['Nada, la serie se detiene', 0],
                ]),
                self::item('Un condicional dice: si hay más de una X, escribe SÍ. En «XOX» corresponde:', [
                    ['SÍ', 0], ['NO', 1], ['X', 0], ['OX', 0],
                ]),
            ],
            'TEST_LABERINTOS_VISOESPACIAL' => [
                self::item('Estás mirando al norte. Giras a la derecha y luego a la derecha. Quedas mirando al:', [
                    ['Norte', 0], ['Este', 0], ['Sur', 1], ['Oeste', 0],
                ]),
                self::item('Un pasillo solo permite: adelante, izquierda, adelante. Partiendo hacia el norte, el segundo tramo va al:', [
                    ['Norte', 0], ['Oeste', 1], ['Este', 0], ['Sur', 0],
                ]),
                self::item('En un cruce, el camino de la izquierda vuelve a la entrada y el de la derecha sigue. Para no repetir la entrada eliges:', [
                    ['Izquierda', 0], ['Derecha', 1], ['Regresar', 0], ['Quedarte en el cruce', 0],
                ]),
                self::item('Una cuadrícula 2×2: entras por la celda suroeste y la salida está al noreste. El camino más corto en pasos ortogonales mide:', [
                    ['1 paso', 0], ['2 pasos', 1], ['3 pasos', 0], ['4 pasos', 0],
                ]),
                self::item('Das tres cuartos de vuelta a la izquierda. Equivale a un cuarto de vuelta a la:', [
                    ['Izquierda', 0], ['Derecha', 1], ['Misma dirección, sin giro', 0], ['Media vuelta extra', 0],
                ]),
                self::item('El mapa dice: dos adelante, uno a la izquierda, uno adelante. Si cada «adelante» es un bloque, avanzas en línea recta:', [
                    ['1 bloque', 0], ['2 bloques y luego cambias', 1], ['3 bloques sin girar', 0], ['4 bloques', 0],
                ]),
                self::item('Ves el número 6 reflejado en un espejo vertical. La figura se parece más a:', [
                    ['6', 0], ['9', 0], ['Una forma abierta al lado contrario', 1], ['8', 0],
                ]),
                self::item('Una pieza en L ocupa tres cuadrados en fila y uno a la derecha del extremo. Al girarla 180 grados, el cuadrado extra queda:', [
                    ['En el mismo extremo y del mismo lado', 0], ['En el extremo opuesto, al lado contrario', 1], ['En el centro', 0], ['Fuera de la pieza', 0],
                ]),
                self::item('Sales de un laberinto y el sol, que estaba a tu izquierda al entrar mirando al norte, queda a tu espalda. Estás mirando al:', [
                    ['Norte', 0], ['Sur', 0], ['Este', 1], ['Oeste', 0],
                ]),
                self::item('Dos caminos rodean un bloque: uno por la izquierda (3 lados) y otro por la derecha (1 lado). El más corto es:', [
                    ['El de la izquierda', 0], ['El de la derecha', 1], ['Son iguales', 0], ['Hay que cruzar el bloque', 0],
                ]),
                self::item('En un plano, la salida está arriba a la derecha de tu posición. El primer giro útil, si solo puedes avanzar hacia los ejes, puede ser:', [
                    ['Norte y luego este', 1], ['Sur y luego oeste', 0], ['Oeste y luego sur', 0], ['Quedarte y girar en el lugar dos vueltas', 0],
                ]),
                self::item('Recorres el borde de un cuadrado en orden: sur, este, norte. Para cerrar el cuadrado, el último lado va al:', [
                    ['Norte', 0], ['Este', 0], ['Sur', 0], ['Oeste', 1],
                ]),
            ],
        ];
    }

    /**
     * @param  list<array{0: string, 1: int}>  $opciones
     * @return array{enunciado: string, opciones: list<array{etiqueta: string, texto: string, puntaje: int}>}
     */
    private static function item(string $enunciado, array $opciones): array
    {
        $letras = ['A', 'B', 'C', 'D'];
        $armadas = [];
        foreach ($opciones as $indice => [$texto, $puntaje]) {
            $armadas[] = ['etiqueta' => $letras[$indice], 'texto' => $texto, 'puntaje' => $puntaje];
        }

        return ['enunciado' => $enunciado, 'opciones' => $armadas];
    }
}
