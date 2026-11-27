<?php

namespace Database\Seeders;

use App\Models\DimensionEvaluada;
use App\Models\OpcionPregunta;
use App\Models\Pregunta;
use App\Models\Prueba;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BancoPreguntasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Carga el banco completo de las 187 preguntas del Cuestionario 16PF (Forma A).
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $pruebaPersonalidad = Prueba::where('codigo', '16PF_FORMA_A')->first();

            if (! $pruebaPersonalidad) {
                return;
            }

            $dimensiones = DimensionEvaluada::pluck('id', 'codigo');

            // Cuestionario completo de 187 preguntas del 16PF (Forma A - Cattell)
            // Cubre todas las 16 dimensiones psicométricas del sistema
            $preguntas16PF = [
                [
                    'orden' => 1,
                    'dimension' => 'CAPACIDAD_ESCOLAR',
                    'enunciado' => 'Entendí las instrucciones de este cuestionario:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 2,
                    'dimension' => 'SUPEREGO',
                    'enunciado' => 'Estoy dispuesto a contestar cada pregunta tan sinceramente como me sea posible:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 3,
                    'dimension' => 'SOCIABILIDAD',
                    'enunciado' => 'Preferiría tener una casa:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'En una zona poblada', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Aislada en el campo', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 4,
                    'dimension' => 'ESTABILIDAD_EMOCIONAL',
                    'enunciado' => 'Tengo la energía suficiente para enfrentarme a mis dificultades:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Siempre', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 5,
                    'dimension' => 'TIMIDEZ',
                    'enunciado' => 'Siento un poco de nerviosismo ante los animales salvajes, aunque estén bien enjaulados:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Indeciso', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 6,
                    'dimension' => 'CONFIANZA',
                    'enunciado' => 'Evito criticar a las personas y a sus ideas:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 7,
                    'dimension' => 'SUMISION',
                    'enunciado' => 'Hago observaciones sarcásticas o directas a las personas que creo que se las merecen:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Siempre', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Nunca', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 8,
                    'dimension' => 'SEVERIDAD',
                    'enunciado' => 'Prefiero la música semiclásica a las canciones populares:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 9,
                    'dimension' => 'SUMISION',
                    'enunciado' => 'Si viera reñir a los niños de los vecinos:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Dejaría que lo solucionaran ellos', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'No sabría qué hacer', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Intentaría reconciliarlos', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 10,
                    'dimension' => 'SOCIABILIDAD',
                    'enunciado' => 'En las reuniones sociales:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Me pongo fácilmente en primer plano', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Término medio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Prefiero quedarme al margen', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 11,
                    'dimension' => 'SEVERIDAD',
                    'enunciado' => 'Preferiría ser:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Ingeniero de construcción', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Indeciso', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Escritor de obras de teatro', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 12,
                    'dimension' => 'SEVERIDAD',
                    'enunciado' => 'Prefiero detenerme a observar a un artista pintando que escuchar a personas discutiendo acaloradamente:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 13,
                    'dimension' => 'CONFIANZA',
                    'enunciado' => 'Casi siempre puedo tolerar a personas vanidosas que se dan importancia:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 14,
                    'dimension' => 'INGENUIDAD',
                    'enunciado' => 'Cuando una persona parece falsa o deshonesta, casi siempre me doy cuenta de inmediato:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Término medio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 15,
                    'dimension' => 'SUPEREGO',
                    'enunciado' => 'Pienso que la gente debería observar las normas morales con mayor rigidez de lo que suele hacerlo:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 16,
                    'dimension' => 'CONFIANZA',
                    'enunciado' => 'La mayoría de las personas con quienes trato me aprecian sinceramente:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 17,
                    'dimension' => 'SOCIABILIDAD',
                    'enunciado' => 'Me gusta participar activamente en proyectos comunitarios o de ayuda social:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 18,
                    'dimension' => 'SOCIABILIDAD',
                    'enunciado' => 'Hablo sobre mis sentimientos íntimos:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Solo si es necesario', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Con facilidad', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 19,
                    'dimension' => 'TRANQUILIDAD',
                    'enunciado' => 'En ocasiones tengo la sensación de un vago peligro o angustia sin motivo aparente:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'A menudo', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Nunca', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 20,
                    'dimension' => 'SEGURIDAD_DE_SI_MISMO',
                    'enunciado' => 'Cuando me regañan o critican por algo que hice, no me siento demasiado abatido:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 21,
                    'dimension' => 'SEGURIDAD_DE_SI_MISMO',
                    'enunciado' => 'Si pudiera volver al pasado con la edad que tengo ahora, planearía mi vida de forma distinta:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Indeciso', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 22,
                    'dimension' => 'INDIFERENCIA',
                    'enunciado' => 'Me gusta organizar y planificar las actividades con bastante anticipación:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 23,
                    'dimension' => 'TRANQUILIDAD',
                    'enunciado' => 'Cuando estoy realizando un trabajo importante, me desconcierta que la gente me interrumpa:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Término medio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 24,
                    'dimension' => 'CONSERVADURISMO',
                    'enunciado' => 'Puedo cambiar de costumbres sin dificultad y sin quejarme:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 25,
                    'dimension' => 'SEVERIDAD',
                    'enunciado' => 'Admiro más la belleza de una joya pulida que la de un arma bien diseñada:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 26,
                    'dimension' => 'SEGURIDAD_DE_SI_MISMO',
                    'enunciado' => 'Me cuesta decidirme cuando tengo varias opciones buenas para elegir:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Raras veces', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'A menudo', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 27,
                    'dimension' => 'CONFIANZA',
                    'enunciado' => 'La mayoría de las personas son honestas por temor a que las descubran:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 28,
                    'dimension' => 'TRANQUILIDAD',
                    'enunciado' => 'A menudo me molesta la lentitud con que algunos realizan las tareas colectivas:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 29,
                    'dimension' => 'TIMIDEZ',
                    'enunciado' => 'Me resulta fácil iniciar una conversación con una persona desconocida:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Siempre', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 30,
                    'dimension' => 'SOCIABILIDAD',
                    'enunciado' => 'Preferiría pasar una tarde libre:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Leyendo un libro apasionante', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Indeciso', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Con un grupo de amigos', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 31,
                    'dimension' => 'SUPEREGO',
                    'enunciado' => 'Me disgusta trabajar en un proyecto en el que se toman decisiones apresuradas que afectan a otros:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 32,
                    'dimension' => 'SEVERIDAD',
                    'enunciado' => 'Si tuviera que elegir, preferiría ser:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Un guardabosques', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Indeciso', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Un profesor de secundaria', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 33,
                    'dimension' => 'CONSERVADURISMO',
                    'enunciado' => 'Pienso que la sociedad debería evolucionar más rápido adoptando cambios radicales:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 34,
                    'dimension' => 'SUPEREGO',
                    'enunciado' => 'Raras veces digo cosas de las que luego tenga que arrepentirme:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 35,
                    'dimension' => 'SUMISION',
                    'enunciado' => 'Me siento cómodo en ambientes donde la gente compite activamente entre sí:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 36,
                    'dimension' => 'SEGURIDAD_DE_SI_MISMO',
                    'enunciado' => 'Cuando algo me sale mal, suelo culpar a mi propia falta de habilidad o esfuerzo:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 37,
                    'dimension' => 'INGENUIDAD',
                    'enunciado' => 'Me gusta analizar la conducta de las personas para entender sus verdaderas motivaciones:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Siempre', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 38,
                    'dimension' => 'CONSERVADURISMO',
                    'enunciado' => 'Prefiero seguir métodos tradicionales probados antes que experimentar con novedades dudosas:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 39,
                    'dimension' => 'TRANQUILIDAD',
                    'enunciado' => 'Puedo desconectarme de mis preocupaciones laborales al llegar a casa:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Fácilmente', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Con esfuerzo', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Casi nunca', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 40,
                    'dimension' => 'INDIFERENCIA',
                    'enunciado' => 'Me gusta que las cosas estén en su lugar exacto y en orden metódico:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Totalmente', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Moderadamente', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Poco', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 41,
                    'dimension' => 'ESTABILIDAD_EMOCIONAL',
                    'enunciado' => 'En situaciones de crisis mantengo la serenidad sin perder el control:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Siempre', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 42,
                    'dimension' => 'SUMISION',
                    'enunciado' => 'Me gusta asumir el liderazgo de un equipo cuando no hay una dirección clara:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Indeciso', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 43,
                    'dimension' => 'CONFIANZA',
                    'enunciado' => 'A veces siento que mis colegas no valoran mis esfuerzos tanto como deberían:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 44,
                    'dimension' => 'SEVERIDAD',
                    'enunciado' => 'Prefiero asistir a una exposición de arte antes que a un evento deportivo masivo:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 45,
                    'dimension' => 'TIMIDEZ',
                    'enunciado' => 'Me resulta incómodo cuando alguien me hace cumplidos excesivos en público:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 46,
                    'dimension' => 'DEPENDENCIA_GRUPAL',
                    'enunciado' => 'Prefiero trabajar de forma independiente en mis propias ideas que coordinar a otros:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 47,
                    'dimension' => 'RETRAIMIENTO',
                    'enunciado' => 'Me esfuerzo por ser el centro de atención cuando asisto a una fiesta:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Raras veces', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'A menudo', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 48,
                    'dimension' => 'SUPEREGO',
                    'enunciado' => 'Suelo ser estricto con los plazos y no me gusta aceptar excusas por demoras:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 49,
                    'dimension' => 'ESTABILIDAD_EMOCIONAL',
                    'enunciado' => 'Me adapto rápidamente a los cambios imprevistos en mi plan de trabajo:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Con dificultad', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 50,
                    'dimension' => 'OBJETIVIDAD',
                    'enunciado' => 'Me gusta reflexionar sobre problemas filosóficos o el sentido de las cosas:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'A menudo', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 51,
                    'dimension' => 'ESTABILIDAD_EMOCIONAL',
                    'enunciado' => 'Si alguien se enfada conmigo, intento calmarlo pacientemente en lugar de discutir:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 52,
                    'dimension' => 'RETRAIMIENTO',
                    'enunciado' => 'Me considero una persona alegre y entusiasta la mayor parte del tiempo:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 53,
                    'dimension' => 'TRANQUILIDAD',
                    'enunciado' => 'Raras veces me desvelo por la noche pensando en los problemas del día siguiente:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 54,
                    'dimension' => 'OBJETIVIDAD',
                    'enunciado' => 'Prefiero leer un artículo sobre avances científicos que sobre política contingente:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 55,
                    'dimension' => 'SUMISION',
                    'enunciado' => 'Me gusta decir claramente lo que pienso, aunque cause cierta incomodidad:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 56,
                    'dimension' => 'CONSERVADURISMO',
                    'enunciado' => 'Me siento más a gusto siguiendo instrucciones claras que inventando mi propio método:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 57,
                    'dimension' => 'SUPEREGO',
                    'enunciado' => 'Cuando compro algo, me aseguro de verificar la garantía y las letras pequeñas:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Siempre', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 58,
                    'dimension' => 'CONFIANZA',
                    'enunciado' => 'Tiendo a confiar en la gente hasta que me demuestren lo contrario:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 59,
                    'dimension' => 'SUPEREGO',
                    'enunciado' => 'Cuando me dan una serie de normas, las sigo estrictamente aunque no me agraden:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 60,
                    'dimension' => 'TIMIDEZ',
                    'enunciado' => 'Me atrae la idea de viajar solo a un país lejano cuya lengua no conozco:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Mucho', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Indeciso', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Poco', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 61,
                    'dimension' => 'OBJETIVIDAD',
                    'enunciado' => 'Me considero una persona práctica y orientada a los resultados concretos:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Totalmente', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En parte', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Poco', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 62,
                    'dimension' => 'SUMISION',
                    'enunciado' => 'Cuando alguien me contradice en una reunión, defiendo con firmeza mi posición:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Siempre', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 63,
                    'dimension' => 'SEVERIDAD',
                    'enunciado' => 'Me conmueven con facilidad las historias o películas dramáticas:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 64,
                    'dimension' => 'INDIFERENCIA',
                    'enunciado' => 'Me resulta sencillo mantener mi mesa de trabajo ordenada y despejada:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 65,
                    'dimension' => 'TRANQUILIDAD',
                    'enunciado' => 'A menudo me siento tenso o impaciente cuando tengo que esperar a los demás:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 66,
                    'dimension' => 'DEPENDENCIA_GRUPAL',
                    'enunciado' => 'Prefiero pertenecer a varias asociaciones o clubes antes que ser un lobo solitario:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 67,
                    'dimension' => 'CAPACIDAD_ESCOLAR',
                    'enunciado' => 'Me gusta aprender temas nuevos aunque no tengan aplicación inmediata en mi empleo:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 68,
                    'dimension' => 'OBJETIVIDAD',
                    'enunciado' => 'En las discusiones suelo basarme en hechos comprobables más que en intuiciones:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Siempre', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 69,
                    'dimension' => 'SEGURIDAD_DE_SI_MISMO',
                    'enunciado' => 'A veces me preocupa lo que los demás piensen o digan a mis espaldas:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'A menudo', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 70,
                    'dimension' => 'CAPACIDAD_ESCOLAR',
                    'enunciado' => 'Me resulta estimulante competir en retos de alta exigencia mental:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Mucho', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Moderadamente', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Poco', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 71,
                    'dimension' => 'TIMIDEZ',
                    'enunciado' => 'Si tengo que presentar un informe ante muchas personas, lo hago sin timidez:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Con nerviosismo', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 72,
                    'dimension' => 'CONSERVADURISMO',
                    'enunciado' => 'Prefiero trabajos donde la rutina esté claramente fijada de antemano:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 73,
                    'dimension' => 'INGENUIDAD',
                    'enunciado' => 'Suelo ser prudente antes de hacer confidencias a personas recién conocidas:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Siempre', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 74,
                    'dimension' => 'ESTABILIDAD_EMOCIONAL',
                    'enunciado' => 'Si un proyecto no sale como esperaba, lo intento de nuevo sin perder el ánimo:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Siempre', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 75,
                    'dimension' => 'SEVERIDAD',
                    'enunciado' => 'Me siento más cómodo resolviendo cosas con las manos que con conceptos teóricos:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 76,
                    'dimension' => 'RETRAIMIENTO',
                    'enunciado' => 'Raras veces me dejo llevar por la euforia o el entusiasmo desmedido:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 77,
                    'dimension' => 'SUPEREGO',
                    'enunciado' => 'Me disgusta la gente que intenta manipular las reglas a su conveniencia:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Mucho', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Moderadamente', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Poco', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 78,
                    'dimension' => 'SEVERIDAD',
                    'enunciado' => 'Cuando tengo tiempo libre, prefiero pasear por la naturaleza que ir de compras:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Indeciso', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 79,
                    'dimension' => 'INGENUIDAD',
                    'enunciado' => 'Tiendo a percibir con exactitud el estado de ánimo de quienes me rodean:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Siempre', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 80,
                    'dimension' => 'ESTABILIDAD_EMOCIONAL',
                    'enunciado' => 'Me siento capaz de manejar situaciones imprevistas con solvencia y rapidez:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Totalmente', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En parte', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Poco', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 81,
                    'dimension' => 'OBJETIVIDAD',
                    'enunciado' => 'En el trabajo colectivo, prefiero ser quien aporta las ideas antes que quien ejecuta:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 82,
                    'dimension' => 'OBJETIVIDAD',
                    'enunciado' => 'A menudo me sorprendo soñando despierto o imaginando proyectos futuros:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Con frecuencia', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 83,
                    'dimension' => 'SOCIABILIDAD',
                    'enunciado' => 'Prefiero rodearme de personas cultas y reflexivas que de bromistas ruidosos:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 84,
                    'dimension' => 'SEGURIDAD_DE_SI_MISMO',
                    'enunciado' => 'Raras veces me siento culpable por errores pasados que ya no puedo cambiar:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 85,
                    'dimension' => 'SUMISION',
                    'enunciado' => 'Me agrada participar en debates sobre temas polémicos y controvertidos:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 86,
                    'dimension' => 'SUPEREGO',
                    'enunciado' => 'Considero que la puntualidad es una muestra fundamental de respeto:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Totalmente', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En parte', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Poco', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 87,
                    'dimension' => 'DEPENDENCIA_GRUPAL',
                    'enunciado' => 'Prefiero viajar en grupo organizado que por mi propia cuenta y riesgo:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Indeciso', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 88,
                    'dimension' => 'TRANQUILIDAD',
                    'enunciado' => 'Me cuesta relajarme completamente cuando quedan tareas pendientes en mi agenda:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 89,
                    'dimension' => 'CONFIANZA',
                    'enunciado' => 'Realmente en el mundo hay más gente solidaria que egoísta:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Indeciso', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 90,
                    'dimension' => 'SEVERIDAD',
                    'enunciado' => 'Me desagradan las personas que se muestran demasiado sentimentales en público:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 91,
                    'dimension' => 'CONSERVADURISMO',
                    'enunciado' => 'Prefiero un trabajo con salario fijo y seguro que uno variable con altos incentivos:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 92,
                    'dimension' => 'SUMISION',
                    'enunciado' => 'Cuando alguien me pide un favor difícil, me cuesta decir que no:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'A menudo', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 93,
                    'dimension' => 'CAPACIDAD_ESCOLAR',
                    'enunciado' => 'Me gusta resolver crucigramas, acertijos y rompecabezas lógicos:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Mucho', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Poco', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 94,
                    'dimension' => 'TIMIDEZ',
                    'enunciado' => 'Las alabanzas y los cumplidos excesivos me hacen sentir algo incómodo:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 95,
                    'dimension' => 'CAPACIDAD_ESCOLAR',
                    'enunciado' => 'Prefiero asistir a una conferencia sobre economía internacional que a una tertulia informal:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Indeciso', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 96,
                    'dimension' => 'INDIFERENCIA',
                    'enunciado' => 'Me resulta fácil mantener la concentración durante varias horas seguidas:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 97,
                    'dimension' => 'ESTABILIDAD_EMOCIONAL',
                    'enunciado' => 'Raras veces me enojo o pierdo la paciencia con la lentitud de los demás:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 98,
                    'dimension' => 'INDIFERENCIA',
                    'enunciado' => 'Me considero una persona meticulosa y detallista con los números y documentos:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Totalmente', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Moderadamente', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Poco', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 99,
                    'dimension' => 'SOCIABILIDAD',
                    'enunciado' => 'A menudo me gustaría tener más tiempo para la introspección y la lectura personal:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 100,
                    'dimension' => 'SUMISION',
                    'enunciado' => 'Me gusta tomar la iniciativa para resolver desacuerdos entre mis compañeros:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 101,
                    'dimension' => 'SEVERIDAD',
                    'enunciado' => 'Prefiero las películas de acción trepidante a las de intriga psicológica:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Indeciso', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 102,
                    'dimension' => 'DEPENDENCIA_GRUPAL',
                    'enunciado' => 'Cuando tomo una decisión importante, busco el consejo de personas experimentadas:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Siempre', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 103,
                    'dimension' => 'SEGURIDAD_DE_SI_MISMO',
                    'enunciado' => 'Raras veces dudo de mi propia capacidad para aprender materias complejas:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 104,
                    'dimension' => 'CONSERVADURISMO',
                    'enunciado' => 'Me gusta vestir de forma sobria y clásica antes que llamar la atención con modas extravagantes:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 105,
                    'dimension' => 'TRANQUILIDAD',
                    'enunciado' => 'Tiendo a preocuparme más de la cuenta por acontecimientos que tal vez nunca ocurran:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'A menudo', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 106,
                    'dimension' => 'SUMISION',
                    'enunciado' => 'Me siento cómodo coordinando a un grupo de personas para lograr una meta común:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Totalmente', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En parte', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Poco', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 107,
                    'dimension' => 'OBJETIVIDAD',
                    'enunciado' => 'Prefiero los libros técnicos y formativos que las novelas de ficción:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 108,
                    'dimension' => 'CONFIANZA',
                    'enunciado' => 'Cuando alguien me decepciona, me cuesta mucho volver a confiar en él:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 109,
                    'dimension' => 'SOCIABILIDAD',
                    'enunciado' => 'Me gusta celebrar los cumpleaños y eventos familiares con gran concurrencia:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Mucho', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Moderadamente', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Poco', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 110,
                    'dimension' => 'ESTABILIDAD_EMOCIONAL',
                    'enunciado' => 'Suelo mantener la calma cuando alguien me ataca verbalmente o insulta:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 111,
                    'dimension' => 'SUPEREGO',
                    'enunciado' => 'Prefiero trabajar en proyectos con normas estrictas que en ambientes con reglas ambiguas:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 112,
                    'dimension' => 'CAPACIDAD_ESCOLAR',
                    'enunciado' => 'Me resulta sencillo aprender programas informáticos y herramientas digitales por mi cuenta:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 113,
                    'dimension' => 'ESTABILIDAD_EMOCIONAL',
                    'enunciado' => 'Cuando estoy cansado, suelo volverme irritable o distante:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'A menudo', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 114,
                    'dimension' => 'CONSERVADURISMO',
                    'enunciado' => 'Prefiero un estilo de vida ordenado y previsible antes que lleno de sorpresas constantes:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 115,
                    'dimension' => 'INGENUIDAD',
                    'enunciado' => 'Me gusta decir la verdad de frente sin rodeos diplomáticos:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Siempre', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 116,
                    'dimension' => 'RETRAIMIENTO',
                    'enunciado' => 'A menudo me siento lleno de energía y ganas de emprender cosas nuevas:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Totalmente', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 117,
                    'dimension' => 'INDIFERENCIA',
                    'enunciado' => 'Me gusta revisar dos o tres veces un trabajo antes de entregarlo definitivamente:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Siempre', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 118,
                    'dimension' => 'TRANQUILIDAD',
                    'enunciado' => 'A veces soy tan feliz que me asusta pensar que mi felicidad pueda no ser duradera:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 119,
                    'dimension' => 'SEVERIDAD',
                    'enunciado' => 'Sería más interesante ser:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Un ingeniero agrónomo', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Indeciso', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Un director de teatro', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 120,
                    'dimension' => 'TIMIDEZ',
                    'enunciado' => 'Me resulta incómodo cuando tengo que pedir dinero o favores personales a conocidos:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Mucho', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Moderadamente', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Poco', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 121,
                    'dimension' => 'OBJETIVIDAD',
                    'enunciado' => 'En general, la sociedad moderna concede excesiva importancia al dinero y las apariencias:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 122,
                    'dimension' => 'DEPENDENCIA_GRUPAL',
                    'enunciado' => 'Prefiero tomar decisiones basadas en mi propio criterio antes que en consensos colectivos:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 123,
                    'dimension' => 'SUMISION',
                    'enunciado' => 'Cuando tengo un desacuerdo con un superior jerárquico, suelo guardar silencio para no generar roces:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'A menudo', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 124,
                    'dimension' => 'TRANQUILIDAD',
                    'enunciado' => 'A menudo me enfado demasiado rápido con la gente que no cumple lo prometido:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 125,
                    'dimension' => 'CONSERVADURISMO',
                    'enunciado' => 'Puedo cambiar viejos hábitos sin dificultad cuando me lo propongo formalmente:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 126,
                    'dimension' => 'CAPACIDAD_ESCOLAR',
                    'enunciado' => 'Me resulta fácil recordar nombres, cifras y detalles que la mayoría olvida:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Término medio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 127,
                    'dimension' => 'TIMIDEZ',
                    'enunciado' => 'Prefiero asistir a una reunión donde conozco a todos los invitados:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 128,
                    'dimension' => 'SUPEREGO',
                    'enunciado' => 'Si veo a una persona cometiendo una infracción menor, no dudo en llamarle la atención:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 129,
                    'dimension' => 'CONFIANZA',
                    'enunciado' => 'Suelo confiar en las promesas de los demás sin pedir garantías formales:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Con reservas', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 130,
                    'dimension' => 'DEPENDENCIA_GRUPAL',
                    'enunciado' => 'Me siento más productivo trabajando en soledad y en silencio:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 131,
                    'dimension' => 'SEVERIDAD',
                    'enunciado' => 'Me considero una persona más reflexiva y analítica que emocional e impulsiva:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Totalmente', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En parte', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Poco', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 132,
                    'dimension' => 'INGENUIDAD',
                    'enunciado' => 'Cuando alguien me pide una opinión sincera sobre su trabajo, no dudo en señalar sus errores:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Siempre', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Con tacto', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 133,
                    'dimension' => 'ESTABILIDAD_EMOCIONAL',
                    'enunciado' => 'Raras veces me preocupo por cuestiones de salud a menos que esté realmente enfermo:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 134,
                    'dimension' => 'SOCIABILIDAD',
                    'enunciado' => 'Prefiero las actividades grupales y los deportes de equipo a los individuales:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Indeciso', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 135,
                    'dimension' => 'INDIFERENCIA',
                    'enunciado' => 'Me gusta planificar con detalle los gastos de mi presupuesto mensual:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Siempre', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 136,
                    'dimension' => 'TRANQUILIDAD',
                    'enunciado' => 'A menudo me cuesta desconectar del trabajo incluso en fines de semana o vacaciones:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 137,
                    'dimension' => 'RETRAIMIENTO',
                    'enunciado' => 'Me gusta asumir riesgos controlados si las probabilidades de éxito son atractivas:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 138,
                    'dimension' => 'CAPACIDAD_ESCOLAR',
                    'enunciado' => 'Prefiero los textos con razonamientos deductivos rigurosos que los basados en anécdotas:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 139,
                    'dimension' => 'TIMIDEZ',
                    'enunciado' => 'Raras veces me ruborizo o me siento tímido ante personas con alto poder o estatus:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 140,
                    'dimension' => 'INDIFERENCIA',
                    'enunciado' => 'Me desagrada la gente que improvisa sin tener un plan B preparado:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Mucho', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Moderadamente', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Poco', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 141,
                    'dimension' => 'SUPEREGO',
                    'enunciado' => 'Cuando me comprometo con una causa, la defiendo con lealtad inquebrantable:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Siempre', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Generalmente', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 142,
                    'dimension' => 'SOCIABILIDAD',
                    'enunciado' => 'Prefiero una conversación profunda con un buen amigo que un cóctel con decenas de conocidos:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 143,
                    'dimension' => 'SEGURIDAD_DE_SI_MISMO',
                    'enunciado' => 'A menudo me siento seguro de haber actuado correctamente sin necesidad de aprobación externa:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 144,
                    'dimension' => 'CONSERVADURISMO',
                    'enunciado' => 'Me gusta probar restaurantes exóticos y comidas que nunca antes había probado:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Mucho', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Poco', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 145,
                    'dimension' => 'CONFIANZA',
                    'enunciado' => 'Me cuesta perdonar a quienes han traicionado mi confianza profesional:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 146,
                    'dimension' => 'OBJETIVIDAD',
                    'enunciado' => 'Prefiero una vivienda funcional y moderna a una antigua con valor histórico:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Indeciso', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 147,
                    'dimension' => 'ESTABILIDAD_EMOCIONAL',
                    'enunciado' => 'Me esfuerzo activamente por no perder los estribos en discusiones acaloradas:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Siempre', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 148,
                    'dimension' => 'INDIFERENCIA',
                    'enunciado' => 'Hago lo posible por no distraerme ni olvidarme de los detalles en mis tareas:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Término medio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 149,
                    'dimension' => 'SUPEREGO',
                    'enunciado' => 'Prefiero ser recordado como una persona honesta y confiable antes que como un genio excéntrico:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Totalmente', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Poco', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 150,
                    'dimension' => 'SUMISION',
                    'enunciado' => 'Me siento cómodo delegando tareas importantes en personas competentes:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Con supervisión', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 151,
                    'dimension' => 'INGENUIDAD',
                    'enunciado' => 'A menudo me gusta reflexionar sobre las razones por las que la gente actúa como actúa:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Con frecuencia', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 152,
                    'dimension' => 'SUMISION',
                    'enunciado' => 'Prefiero trabajar en un entorno competitivo antes que en uno paternalista:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 153,
                    'dimension' => 'SEGURIDAD_DE_SI_MISMO',
                    'enunciado' => 'Raras veces me desanimo cuando las críticas hacia mi trabajo son severas:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En duda', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 154,
                    'dimension' => 'CONSERVADURISMO',
                    'enunciado' => 'Me gusta participar en iniciativas de modernización e innovación en mi lugar de trabajo:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Mucho', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Moderadamente', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Poco', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 155,
                    'dimension' => 'SEGURIDAD_DE_SI_MISMO',
                    'enunciado' => 'Aunque las probabilidades de que algo tenga éxito estén en contra, sigo adelante si confío en mi plan:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 156,
                    'dimension' => 'SUPEREGO',
                    'enunciado' => 'Me desagrada que la gente llegue tarde a las citas pactadas:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Mucho', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Moderadamente', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Poco', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 157,
                    'dimension' => 'SEVERIDAD',
                    'enunciado' => 'Prefiero leer poesía o literatura lírica antes que reportes estadísticos:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Indeciso', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 158,
                    'dimension' => 'SUMISION',
                    'enunciado' => 'En situaciones de emergencia, suelo ser la persona que toma el control de las acciones:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 159,
                    'dimension' => 'CAPACIDAD_ESCOLAR',
                    'enunciado' => 'Me resulta sencillo explicar conceptos difíciles de forma sencilla a los demás:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Término medio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 160,
                    'dimension' => 'RETRAIMIENTO',
                    'enunciado' => 'Raras veces me siento aburrido o sin saber qué hacer con mi tiempo:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 161,
                    'dimension' => 'DEPENDENCIA_GRUPAL',
                    'enunciado' => 'Prefiero trabajar bajo supervisión periódica que con total libertad y sin rumbo fijado:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 162,
                    'dimension' => 'SUPEREGO',
                    'enunciado' => 'Me esfuerzo por ser justo y equitativo incluso con personas que no me caen simpáticas:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Siempre', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Casi siempre', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 163,
                    'dimension' => 'TRANQUILIDAD',
                    'enunciado' => 'A menudo siento tensión en los músculos del cuello o la espalda tras un día agitado:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Frecuentemente', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 164,
                    'dimension' => 'SOCIABILIDAD',
                    'enunciado' => 'Me considero una persona cordial que saluda con agrado a todo el personal:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Totalmente', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Moderadamente', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Poco', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 165,
                    'dimension' => 'OBJETIVIDAD',
                    'enunciado' => 'Prefiero las decisiones respaldadas por datos empíricos que por convicciones personales:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 166,
                    'dimension' => 'ESTABILIDAD_EMOCIONAL',
                    'enunciado' => 'Raras veces me lamento por las oportunidades perdidas en el pasado:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 167,
                    'dimension' => 'CAPACIDAD_ESCOLAR',
                    'enunciado' => 'Me gusta asistir a seminarios y cursos de actualización profesional:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Mucho', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Moderadamente', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Poco', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 168,
                    'dimension' => 'RETRAIMIENTO',
                    'enunciado' => 'En las fiestas o reuniones, prefiero escuchar las conversaciones que acaparar la palabra:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 169,
                    'dimension' => 'INDIFERENCIA',
                    'enunciado' => 'Me considero una persona disciplinada que cumple sus metas aunque cueste sacrificio:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Totalmente', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'En parte', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Poco', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 170,
                    'dimension' => 'INGENUIDAD',
                    'enunciado' => 'Tiendo a desconfiar de las ofertas comerciales que prometen ganancias fáciles e instantáneas:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Siempre', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 171,
                    'dimension' => 'DEPENDENCIA_GRUPAL',
                    'enunciado' => 'Prefiero trabajar en un equipo cohesionado que avanzar en solitario:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 172,
                    'dimension' => 'RETRAIMIENTO',
                    'enunciado' => 'A menudo me siento motivado por el deseo de superarme profesionalmente:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Totalmente', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Moderadamente', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Poco', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 173,
                    'dimension' => 'ESTABILIDAD_EMOCIONAL',
                    'enunciado' => 'Raras veces me enfado cuando las cosas no salen de acuerdo al plan inicial:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 174,
                    'dimension' => 'INGENUIDAD',
                    'enunciado' => 'Me gusta expresar mis opiniones de forma prudente y diplomática para evitar conflictos:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Siempre', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'A veces', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Raras veces', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 175,
                    'dimension' => 'INDIFERENCIA',
                    'enunciado' => 'Prefiero las instrucciones por escrito a las instrucciones orales rápidas:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 176,
                    'dimension' => 'SOCIABILIDAD',
                    'enunciado' => 'Me resulta sencillo adaptarme al estilo de trabajo de nuevos compañeros:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Con esfuerzo', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 177,
                    'dimension' => 'CAPACIDAD_ESCOLAR',
                    'enunciado' => '\'Justicia\' es a \'leyes\' como \'idea\' es a:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Palabras', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Sentimientos', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Teorías', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 178,
                    'dimension' => 'CAPACIDAD_ESCOLAR',
                    'enunciado' => '¿Cuál de las siguientes palabras no corresponde al grupo?:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Correr', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Ver', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Tocar', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 179,
                    'dimension' => 'CAPACIDAD_ESCOLAR',
                    'enunciado' => '\'Cuchillo\' es a \'cortar\' como \'pala\' es a:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cavar', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Filosa', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Tierra', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 180,
                    'dimension' => 'CAPACIDAD_ESCOLAR',
                    'enunciado' => 'Si la aguja grande del reloj apunta a las 12 y la pequeña a las 9, el ángulo formado es de:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => '90 grados', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => '180 grados', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => '45 grados', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 181,
                    'dimension' => 'CAPACIDAD_ESCOLAR',
                    'enunciado' => '¿Cuál de los siguientes números continúa la serie: 2, 4, 8, 16, ...?:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => '32', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => '24', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => '64', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 182,
                    'dimension' => 'CAPACIDAD_ESCOLAR',
                    'enunciado' => '\'Rápido\' es a \'lento\' como \'ancho\' es a:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Estrecho', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Largo', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Alto', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 183,
                    'dimension' => 'CAPACIDAD_ESCOLAR',
                    'enunciado' => 'Si todos los mamíferos respiran y la ballena es un mamífero, entonces la ballena:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Respira', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Nada', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Tiene branquias', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 184,
                    'dimension' => 'CAPACIDAD_ESCOLAR',
                    'enunciado' => '\'Primero\' es a \'segundo\' como \'enero\' es a:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Febrero', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Diciembre', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Año', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 185,
                    'dimension' => 'INDIFERENCIA',
                    'enunciado' => 'Me agradan los trabajos que requieren concentración sostenida y habilidades precisas:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Mucho', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Intermedio', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Poco', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 186,
                    'dimension' => 'RETRAIMIENTO',
                    'enunciado' => 'Me considero una persona enérgica que se mantiene activa durante toda la jornada:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Cierto', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Indeciso', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'Falso', 'puntaje' => 0.0],
                    ],
                ],
                [
                    'orden' => 187,
                    'dimension' => 'SUPEREGO',
                    'enunciado' => 'Estoy seguro de haber contestado con sinceridad y de no haber dejado ninguna pregunta sin contestar:',
                    'opciones' => [
                        ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2.0],
                        ['etiqueta' => 'B', 'texto' => 'Indeciso', 'puntaje' => 1.0],
                        ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0.0],
                    ],
                ],
            ];

            // Limpiar preguntas previas de la prueba para permitir re-ejecución limpia
            $preguntasAnteriores = Pregunta::query()->where('prueba_id', $pruebaPersonalidad->id)->pluck('id');
            if ($preguntasAnteriores->isNotEmpty()) {
                DB::table('talento.respuesta_intento')->whereIn('pregunta_id', $preguntasAnteriores)->delete();
                Pregunta::query()->where('prueba_id', $pruebaPersonalidad->id)->delete();
            }

            foreach ($preguntas16PF as $item) {
                $dimensionId = $dimensiones[$item['dimension']] ?? null;

                $pregunta = Pregunta::create([
                    'prueba_id' => $pruebaPersonalidad->id,
                    'tipo_prueba' => 'TEST',
                    'dimension_id' => $dimensionId,
                    'orden' => $item['orden'],
                    'enunciado' => $item['enunciado'],
                ]);

                foreach ($item['opciones'] as $opc) {
                    OpcionPregunta::create([
                        'pregunta_id' => $pregunta->id,
                        'etiqueta' => $opc['etiqueta'],
                        'texto' => $opc['texto'],
                        'puntaje' => $opc['puntaje'],
                    ]);
                }
            }
        });
    }
}
