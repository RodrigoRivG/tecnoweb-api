<?php

namespace Database\Seeders;

use App\Models\OpcionPregunta;
use App\Models\Pregunta;
use App\Models\Prueba;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BancoPreguntasLecturaTest1Seeder extends Seeder
{
    /**
     * Banco oficial de preguntas del Test 1: Lectura Comprensiva (Habilidades y Aptitudes Específicas).
     * Fuente: TIPOS-DE-TEST/Habilidades-Aptitudes-específicas/test/4 (Páginas 181 a 185).
     * Total: 20 preguntas oficiales con claves verificadas al 100%.
     */
    private const PREGUNTAS = [
        1 => [
            'enunciado' => "PASAJE I\nLas multicopistas son útiles tanto en la oficina como en la escuela. Los profesores las emplean para preparar exámenes y hojas de ejercicios; los oficinistas las utilizan para cartas informativas y memorándums. No se precisa ser un impresor profesional para manejar una multicopista moderna; cualquiera puede aprender a hacerlo en pocos minutos.\n\n¿De acuerdo con este pasaje, cuál de las siguientes afirmaciones es la más cierta?",
            'clave' => 'D',
            'opciones' => [
                'A' => 'No todo el mundo puede aprender a manejar una multicopista moderna.',
                'B' => 'Las multicopistas sólo son útiles en las oficinas.',
                'C' => 'Las multicopistas sólo son útiles en las escuelas.',
                'D' => 'Cualquiera puede aprender a manejar una multicopista en pocos minutos.',
                'E' => 'Los profesores no utilizan las multicopistas.',
            ],
        ],
        2 => [
            'enunciado' => "PASAJE II\nLa televisión es uno de los medios de comunicación de masas más importantes de la sociedad moderna. Es incluso más influyente que la imprenta. La televisión permite ver y oír las noticias tal y como se producen, y lleva a los hogares acontecimientos de los que de otro modo nunca tendríamos constancia directa.\n\n¿Según este pasaje, qué afirmación es más cierta?",
            'clave' => 'C',
            'opciones' => [
                'A' => 'La televisión es el único medio de comunicación de masas en la sociedad moderna.',
                'B' => 'La televisión es menos influyente que la imprenta.',
                'C' => 'La televisión es más influyente que la imprenta.',
                'D' => 'La televisión nunca nos lleva a acontecimientos de los que de otro modo no tendríamos noticia.',
                'E' => 'La imprenta es más influyente que la televisión.',
            ],
        ],
        3 => [
            'enunciado' => "PASAJE III\nCuando una industria experimenta un gran crecimiento, a menudo atrae a personas de otras partes del país en busca de trabajo. Este influjo de nuevos trabajadores puede causar problemas a la comunidad local si no hay viviendas, escuelas u otros servicios suficientes para satisfacer la creciente demanda.\n\n¿Según este pasaje, cuál de las siguientes afirmaciones es la más cierta?",
            'clave' => 'B',
            'opciones' => [
                'A' => 'El crecimiento industrial nunca atrae a personas de otras partes del país.',
                'B' => 'El rápido crecimiento de una industria puede crear problemas a la comunidad local.',
                'C' => 'Siempre hay suficientes viviendas y escuelas para satisfacer la demanda causada por el crecimiento de una industria.',
                'D' => 'La gente nunca se traslada de una parte a otra del país en busca de trabajo.',
                'E' => 'El crecimiento de una industria nunca genera una mayor demanda de servicios.',
            ],
        ],
        4 => [
            'enunciado' => "PASAJE IV\nLos telegramas son un medio rápido y eficaz de enviar mensajes urgentes. Sin embargo, como el coste del telegrama depende del número de palabras utilizadas, es importante redactar el mensaje de la manera más concisa y clara posible para evitar gastos innecesarios.\n\n¿Según este pasaje, qué afirmación es más cierta?",
            'clave' => 'D',
            'opciones' => [
                'A' => 'El coste de un telegrama es independiente del número de palabras.',
                'B' => 'Los telegramas son una forma lenta de enviar mensajes urgentes.',
                'C' => 'No es importante redactar un telegrama de forma concisa.',
                'D' => 'El coste de un telegrama se basa en el número de palabras empleadas.',
                'E' => 'Los telegramas no se utilizan para mensajes urgentes.',
            ],
        ],
        5 => [
            'enunciado' => "PASAJE V\nEl servicio de correos ha introducido un sistema de buzones de sugerencias para animar a los empleados a proponer ideas que mejoren la eficacia de las operaciones. Se ofrecen recompensas monetarias por aquellas sugerencias que sean adoptadas por el servicio.\n\n¿Según este pasaje, qué afirmación es más cierta?",
            'clave' => 'B',
            'opciones' => [
                'A' => 'El servicio de correos no desea sugerencias de sus empleados.',
                'B' => 'Se conceden recompensas monetarias por las sugerencias adoptadas.',
                'C' => 'Los buzones de sugerencias no son para uso de los empleados.',
                'D' => 'El sistema de sugerencias no pretende mejorar la eficacia de las operaciones.',
                'E' => 'A los empleados no se les paga por sus sugerencias adoptadas.',
            ],
        ],
        6 => [
            'enunciado' => "PASAJE VI\nEl teatro ha sido un elemento de fundamental importancia en la evolución del hombre y de su sociedad. A través de las edades, el teatro ha sido utilizado no sólo como entretenimiento, sino también para educar a la gente y para inculcarle valores morales y religiosos. El teatro refleja las esperanzas, los temores, los ideales y los fracasos de la humanidad; ofrece una imagen vívida de las épocas que representa. Por tanto, el estudio del teatro es esencial para una comprensión completa del desarrollo humano.\n\n¿El teatro ha sido utilizado a través de los tiempos para?",
            'clave' => 'D',
            'opciones' => [
                'A' => 'entretener a los dioses',
                'B' => 'apaciguar la cólera divina',
                'C' => 'satisfacer los impulsos primitivos del hombre',
                'D' => 'transmitir a la gente valores morales y religiosos',
                'E' => 'nada de esto',
            ],
        ],
        7 => [
            'enunciado' => "PASAJE VI (Continuación)\n«El teatro refleja las esperanzas, los temores, los ideales y los fracasos de la humanidad; ofrece una imagen vívida de las épocas que representa...»\n\n¿Qué aspecto de la condición humana no se menciona explícitamente como reflejado por el teatro?",
            'clave' => 'C',
            'opciones' => [
                'A' => 'los temores',
                'B' => 'los ideales',
                'C' => 'los instintos',
                'D' => 'las esperanzas',
                'E' => 'los fracasos',
            ],
        ],
        8 => [
            'enunciado' => "PASAJE VI (Continuación)\n«El teatro ha sido un elemento de fundamental importancia en la evolución del hombre y de su sociedad...»\n\n¿Según el pasaje, la función principal del teatro ha sido?",
            'clave' => 'D',
            'opciones' => [
                'A' => 'mostrar la superioridad de ciertas épocas',
                'B' => 'divertir al público ocioso',
                'C' => 'reemplazar los ritos religiosos',
                'D' => 'relatar los cambios y el desarrollo del ser humano',
                'E' => 'todo lo anterior',
            ],
        ],
        9 => [
            'enunciado' => "PASAJE VI (Continuación)\n«Por tanto, el estudio del teatro es esencial para una comprensión completa del desarrollo humano.»\n\n¿Para qué sirve el estudio del teatro de acuerdo con el autor?",
            'clave' => 'E',
            'opciones' => [
                'A' => 'comprender el desarrollo humano',
                'B' => 'analizar las épocas pasadas',
                'C' => 'apreciar la evolución social',
                'D' => 'entender los valores morales del hombre',
                'E' => 'todo lo anterior',
            ],
        ],
        10 => [
            'enunciado' => "PASAJE VI (Conclusión)\n«El teatro refleja las esperanzas, los temores, los ideales y los fracasos de la humanidad; ofrece una imagen vívida de las épocas que representa.»\n\n¿El teatro ofrece principalmente?",
            'clave' => 'C',
            'opciones' => [
                'A' => 'un escape de la realidad cotidiana',
                'B' => 'una visión idealizada del pasado',
                'C' => 'una imagen vívida de las épocas que representa',
                'D' => 'un medio exclusivo de instrucción religiosa',
                'E' => 'un registro arqueológico',
            ],
        ],
        11 => [
            'enunciado' => "PASAJE VII\nLos mexicanos que en los territorios cedidos no conserven el carácter de ciudadanos de la República Mexicana, según lo estipulado en el artículo anterior, serán incorporados a la Unión de los Estados Unidos y admitidos tan pronto como sea posible, según los principios de la Constitución Federal, al goce de todos los derechos de los ciudadanos de los Estados Unidos. Entre tanto serán protegidos en el libre goce de su libertad y propiedad, y asegurados en el libre ejercicio de su religión sin restricción alguna.\n\n¿El propósito principal de este pasaje es?",
            'clave' => 'D',
            'opciones' => [
                'A' => 'delimitar las fronteras territoriales',
                'B' => 'establecer impuestos sobre la propiedad',
                'C' => 'regular el comercio entre México y los Estados Unidos',
                'D' => 'declarar las intenciones sobre la ciudadanía de los habitantes de los territorios cedidos',
                'E' => 'prohibir el ejercicio religioso',
            ],
        ],
        12 => [
            'enunciado' => "PASAJE VII (Continuación)\n«Los mexicanos que en los territorios cedidos no conserven el carácter de ciudadanos de la República Mexicana... serán incorporados a la Unión de los Estados Unidos y admitidos tan pronto como sea posible...»\n\n¿De acuerdo con este artículo, los mexicanos que permanezcan en los territorios cedidos recibirán de inmediato?",
            'clave' => 'E',
            'opciones' => [
                'A' => 'tierras gratuitas de cultivo',
                'B' => 'pensiones económicas del gobierno de los EE.UU.',
                'C' => 'el derecho inmediato a ocupar cargos públicos federales',
                'D' => 'la nacionalidad de un tercer estado neutral',
                'E' => 'nada de esto',
            ],
        ],
        13 => [
            'enunciado' => "PASAJE VII (Continuación)\n«Entre tanto serán protegidos en el libre goce de su libertad y propiedad, y asegurados en el libre ejercicio de su religión sin restricción alguna.»\n\n¿Qué garantiza el tratado a quienes no opten por conservar la nacionalidad mexicana?",
            'clave' => 'B',
            'opciones' => [
                'A' => 'la exención permanente de leyes estadounidenses',
                'B' => 'la protección en el libre goce de su libertad, propiedad y religión',
                'C' => 'su traslado forzoso al sur del Río Bravo',
                'D' => 'la concesión inmediata de títulos nobiliarios',
                'E' => 'la renuncia obligada a sus posesiones inmuebles',
            ],
        ],
        14 => [
            'enunciado' => "PASAJE VII (Continuación)\n«...serán incorporados a la Unión de los Estados Unidos y admitidos tan pronto como sea posible, según los principios de la Constitución Federal, al goce de todos los derechos de los ciudadanos de los Estados Unidos...»\n\n¿La incorporación de estos habitantes como ciudadanos de los Estados Unidos se hará?",
            'clave' => 'B',
            'opciones' => [
                'A' => 'en un plazo fijo e improrrogable de diez años',
                'B' => 'tan pronto como sea posible conforme a los principios de la Constitución Federal',
                'C' => 'únicamente si aprueban un examen en inglés',
                'D' => 'a condición de que vendan sus propiedades a colonos norteamericanos',
                'E' => 'tras una votación de plebiscito en cada poblado',
            ],
        ],
        15 => [
            'enunciado' => "PASAJE VII (Conclusión)\n«Entre tanto serán protegidos en el libre goce de su libertad y propiedad, y asegurados en el libre ejercicio de su religión sin restricción alguna.»\n\n¿Respecto a la libertad de cultos en esos territorios, el texto establece que?",
            'clave' => 'E',
            'opciones' => [
                'A' => 'se prohibirá cualquier manifestación religiosa no oficial',
                'B' => 'se restringirá gradualmente con el transcurso de los años',
                'C' => 'estará supeditada a permisos concedidos por las autoridades locales',
                'D' => 'deberá adaptarse a la religión predominante en la Unión',
                'E' => 'nada de lo anterior',
            ],
        ],
        16 => [
            'enunciado' => "PASAJE VIII\nEl lucio es un pez de agua dulce sumamente voraz y agresivo, a menudo denominado el «tiburón» de los ríos y lagos europeos o el «lobo de las aguas». De tamaño considerable, pues puede superar el metro de longitud y alcanzar un peso de más de veinte kilogramos, ataca prácticamente a cualquier criatura viva de su entorno que no exceda excesivamente sus dimensiones: peces de toda especie (incluso de la suya propia), ranas, pequeños mamíferos como ratas o nutrias jóvenes e incluso aves acuáticas.\n\nSu apetito es legendario; se ha dicho con frecuencia que «el estómago no tiene oídos», queriendo denotar la ferocidad ciega con la que acomete a sus presas sin reparar en peligros. Autores y naturalistas célebres como Sir Francis Bacon y el naturalista Gesner han relatado asombrosas anécdotas sobre su desmedida voracidad y longevidad, llegándose a afirmar que pueden vivir más de dos siglos —como aquel célebre lucio pescado en Suabia en 1497 con un anillo de bronce colocado supuestamente por orden del emperador Federico II en 1230—. En una ocasión, una mujer de Killingworth y un tal Mr. Seagrave testificaron haber visto lucios atacar incluso patas de ganado que abrevaba en la orilla. Nada escapa a su implacable acecho.\n\n¿El tamaño del lucio descrito en este pasaje se considera?",
            'clave' => 'C',
            'opciones' => [
                'A' => 'diminuto',
                'B' => 'insignificante',
                'C' => 'de tamaño medio a considerable',
                'D' => 'exclusivamente microscópico',
                'E' => 'el mayor entre todos los vertebrados marinos',
            ],
        ],
        17 => [
            'enunciado' => "PASAJE VIII (Continuación)\n«Autores y naturalistas célebres como Sir Francis Bacon y el naturalista Gesner han relatado asombrosas anécdotas sobre su desmedida voracidad y longevidad, llegándose a afirmar que pueden vivir más de dos siglos...»\n\n¿Según las anécdotas populares citadas sobre la longevidad del pez lucio, se ha llegado a afirmar que pueden vivir hasta?",
            'clave' => 'D',
            'opciones' => [
                'A' => 'veinte años',
                'B' => 'cincuenta años',
                'C' => 'un siglo',
                'D' => 'dos siglos',
                'E' => 'como cualquier pez de agua salada',
            ],
        ],
        18 => [
            'enunciado' => "PASAJE VIII (Continuación)\n«...ataca prácticamente a cualquier criatura viva de su entorno que no exceda excesivamente sus dimensiones: peces de toda especie (incluso de la suya propia), ranas, pequeños mamíferos como ratas o nutrias jóvenes e incluso aves acuáticas...»\n\n¿Según este pasaje se sabe que un lucio ataca a cualquier ser vivo salvo un(a)?",
            'clave' => 'B',
            'opciones' => [
                'A' => 'mula',
                'B' => 'lobo',
                'C' => 'nutria',
                'D' => 'joven',
                'E' => 'carpa',
            ],
        ],
        19 => [
            'enunciado' => "PASAJE VIII (Continuación)\n«Su apetito es legendario; se ha dicho con frecuencia que «el estómago no tiene oídos», queriendo denotar la ferocidad ciega con la que acomete a sus presas sin reparar en peligros...»\n\n¿La negativa del lucio a abandonar su presa, incluso aunque lo hayan sacado totalmente del agua, se explica con la siguiente metáfora?",
            'clave' => 'D',
            'opciones' => [
                'A' => 'los lucios no tienen pies',
                'B' => 'los tiranos no tienen piedad',
                'C' => 'los lucios se alimentan de la Pontederia cordata',
                'D' => 'el estómago no tiene oídos',
                'E' => 'la vida se mantiene con la muerte de otros',
            ],
        ],
        20 => [
            'enunciado' => "PASAJE VIII (Conclusión)\n«Autores y naturalistas célebres como Sir Francis Bacon y el naturalista Gesner han relatado asombrosas anécdotas... En una ocasión, una mujer de Killingworth y un tal Mr. Seagrave testificaron haber visto lucios... como aquel célebre lucio pescado en Suabia en 1497 con un anillo de bronce colocado supuestamente por orden del emperador Federico II en 1230.»\n\n¿Las «personas de crédito» que, de acuerdo con el autor, hacen las afirmaciones incluidas en este pasaje son todas las siguientes salvo?",
            'clave' => 'A',
            'opciones' => [
                'A' => 'Federico II',
                'B' => 'Sir Francis Bacon',
                'C' => 'Gesner',
                'D' => 'una mujer de Killingworth',
                'E' => 'Mr. Seagrave',
            ],
        ],
    ];

    public function run(): void
    {
        $prueba = Prueba::query()->where('codigo', 'TEST_LECTURA_COMPRENSIVA_1')->firstOrFail();

        $this->command?->info("Poblando banco de preguntas para la prueba [{$prueba->codigo}] (ID: {$prueba->id})...");

        DB::transaction(function () use ($prueba) {
            // 1. Limpiar respuestas de intentos previos referidas a estas preguntas
            $preguntasIds = Pregunta::query()->where('prueba_id', $prueba->id)->pluck('id');
            DB::table('talento.respuesta_intento')->whereIn('pregunta_id', $preguntasIds)->delete();

            // 2. Limpiar preguntas previas de esta prueba (en cascada elimina opciones)
            Pregunta::query()->where('prueba_id', $prueba->id)->delete();

            foreach (self::PREGUNTAS as $nro => $item) {
                $pregunta = Pregunta::create([
                    'prueba_id' => $prueba->id,
                    'tipo_prueba' => 'TEST',
                    'orden' => $nro,
                    'enunciado' => $item['enunciado'],
                ]);

                foreach ($item['opciones'] as $letra => $texto) {
                    $esCorrecta = ($letra === $item['clave']);
                    OpcionPregunta::create([
                        'pregunta_id' => $pregunta->id,
                        'etiqueta' => $letra,
                        'texto' => $texto,
                        'puntaje' => $esCorrecta ? '1.00' : '0.00',
                    ]);
                }
            }
        });

        $this->command?->info('¡20 preguntas oficiales de Test 1: Lectura Comprensiva (100% del test) registradas con éxito!');
    }
}
