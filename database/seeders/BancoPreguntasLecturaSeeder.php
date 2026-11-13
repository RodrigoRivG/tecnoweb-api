<?php

namespace Database\Seeders;

use App\Models\OpcionPregunta;
use App\Models\Pregunta;
use App\Models\Prueba;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BancoPreguntasLecturaSeeder extends Seeder
{
    /**
     * Banco oficial de preguntas del Test de Habilidades y Aptitudes Específicas (Comprensión y Análisis de Textos Técnicos).
     * Fuente: Escáner_20261111 (2) a (5).png y Plantilla de corrección oficial Escáner_20261111.png.
     */
    private const PREGUNTAS = [
        1 => [
            'enunciado' => "PASAJE I\nLos rayos X han entrado en el mundo de la empresa. Desarrollados al principio para la diagnosis de dolencias humanas, funcionan ahora en las plantas de empaquetado, en fundiciones, en las estaciones de servicio, contribuyendo de múltiples modos a la precisión de la industria.\n\n¿En este pasaje, cuál de las siguientes afirmaciones se apoya más firmemente?",
            'clave' => 'B',
            'opciones' => [
                'A' => 'Fue desarrollado en un principio como una ayuda directa al mundo de la empresa.',
                'B' => 'Se utiliza para mejorar el funcionamiento y precisión de la industria.',
                'C' => 'Es más preciso para las plantas de empaquetamiento que para las fundiciones.',
                'D' => 'Aumenta el rendimiento de industrias como las estaciones de servicio.',
                'E' => 'Se utiliza también primordialmente en los aeropuertos comerciales.',
            ],
        ],
        2 => [
            'enunciado' => "PASAJE II\nUna encuesta realizada para determinar qué materias han ayudado más a los estudiantes en sus trabajos demuestra que la mecanografía es superior a todas las demás materias en el grupo empresarial. También es una de las materias a las que dan más valor los estudiantes universitarios, quienes la repetirían si volvieran a realizar el bachillerato.\n\n¿En este pasaje, cuál es la afirmación que mejor se apoya?",
            'clave' => 'A',
            'opciones' => [
                'A' => 'La capacidad mecanográfica se valora altamente tanto en la empresa como en la universidad.',
                'B' => 'Los universitarios que vuelven a hacer el bachillerato optan de nuevo por la mecanografía.',
                'C' => 'Los universitarios con conocimientos mecanográficos hacen mejores trabajos en la universidad.',
                'D' => 'Los que saben mecanografía tienen asegurado el éxito en el mundo empresarial.',
                'E' => 'Los que no saben mecanografía tienen menos éxito que los que conocen la materia.',
            ],
        ],
        3 => [
            'enunciado' => "PASAJE III\nLa iluminación directa es la menos satisfactoria. Con toda seguridad, los reflectores situados en la mesa de trabajo o el techo que difuminan todos los rayos hacia abajo producen brillo excesivo en la superficie de trabajo.\n\n¿Por qué se considera la luz directa el método de iluminación menos satisfactorio?",
            'clave' => 'C',
            'opciones' => [
                'A' => 'Porque la luz difuminada produce tensión ocular inmediata.',
                'B' => 'Porque la sombra de la lámpara individual no está científicamente pensada.',
                'C' => 'Porque la superficie de trabajo suele deslumbrar o sufrir reflejos molestos por causa del brillo.',
                'D' => 'Porque la iluminación directa es químicamente nociva para los ojos.',
                'E' => 'Porque carece de la brillantez necesaria para realizar tareas minuciosas.',
            ],
        ],
        4 => [
            'enunciado' => "PASAJE IV\nLos peligros de la antigua triple amenaza del quirófano —shock, hemorragia e infección— han quedado prácticamente eliminados. Para combatir el shock y la hemorragia se emplea la transfusión de sangre. También se utiliza para fortalecer a un paciente tan debilitado por la enfermedad que sin ella sería imposible operarle.\n\n¿Qué afirmación se sustenta primordialmente en este pasaje?",
            'clave' => 'B',
            'opciones' => [
                'A' => 'La asepsia hospitalaria ha eliminado por completo toda posibilidad de contagio.',
                'B' => 'Las intervenciones quirúrgicas no revisten hoy en día los graves peligros de antaño.',
                'C' => 'Toda operación grave debe ir precedida obligatoriamente de una transfusión.',
                'D' => 'La técnica quirúrgica moderna depende exclusivamente de los egresados noveles.',
                'E' => 'En épocas antiguas no existían quirófanos acondicionados.',
            ],
        ],
        5 => [
            'enunciado' => "PASAJE V\nEl hierro se utiliza para hacer los puentes y rascacielos, los metros y los vapores, los ferrocarriles y los automóviles, y casi todo tipo de maquinaria... además de millones de artículos, desde la guadaña del labrador a la aguja del sastre.\n\n¿Qué conclusión sobre el hierro se desprende primordialmente del texto?",
            'clave' => 'B',
            'opciones' => [
                'A' => 'Es el metal de mayor abundancia sobre la corteza terrestre.',
                'B' => 'Posee una enorme multiplicidad y versatilidad de aplicaciones útiles.',
                'C' => 'Es el metal más resistente de todos los conocidos.',
                'D' => 'Constituye el único material empleado en rascacielos y puentes.',
                'E' => 'Representa el metal de menor costo en el comercio global.',
            ],
        ],
        6 => [
            'enunciado' => "PASAJE VI\nAunque se cuiden ovejas como negocio principal, y no como producto secundario de la granja, es extremadamente difícil producir lana de calidad uniforme. En una sola oveja la hay de por lo menos cuatro grados. En cada estación no hay dos ovejas del rebaño que tengan exactamente el mismo grado de lana. Además, como la calidad se ve afectada por el alimento que ingieren las ovejas, por el suelo en el que pastan y por el clima, en un año no hay dos ovejas que produzcan el mismo tipo de lana.\n\n¿De acuerdo con el texto, qué factor influye directamente en la calidad de la lana?",
            'clave' => 'A',
            'opciones' => [
                'A' => 'Las características del suelo donde se alimentan y pastan los rebaños.',
                'B' => 'El tratamiento industrial posterior como derivado cárnico.',
                'C' => 'La uniformidad climática constante entre estaciones.',
                'D' => 'La cría orientada exclusivamente a vestimentas institucionales.',
                'E' => 'La selección genética artificial sin importar el clima circundante.',
            ],
        ],
        7 => [
            'enunciado' => "PASAJE VII\nComo el gobierno sólo puede gastar lo que obtiene de la gente, y en última instancia esa cantidad está limitada por la capacidad y el deseo de la gente de pagar los impuestos, es muy importante que la gente tenga una información completa de la obra del gobierno.\n\n¿Cuál es la premisa fundamental sobre el financiamiento público expresada en el texto?",
            'clave' => 'B',
            'opciones' => [
                'A' => 'Los empleados públicos deben intercambiar funciones periódicamente en su dependencia.',
                'B' => 'Los recursos del gobierno se sustentan en última instancia en la voluntad y pago del pueblo.',
                'C' => 'La difusión de la gestión incrementa por sí sola el gasto de las entidades.',
                'D' => 'La acción del Estado se ha reducido por la falta de recaudación impositiva.',
                'E' => 'Los contribuyentes desconocen las metas tributarias del estado.',
            ],
        ],
        8 => [
            'enunciado' => "PASAJE VIII\nLa enseñanza secundaria y la universitaria deben aceptar la responsabilidad de preparar al estudiante para que obtenga un trabajo. Como la capacidad de escribir una buena carta de solicitud es uno de los primeros pasos de ese objetivo, todo profesor debería hacer lo que estuviera en su mano para ayudar al estudiante a aprender a escribir esas cartas.\n\n¿Qué se deduce respecto a las competencias comunicativas y la inserción laboral?",
            'clave' => 'A',
            'opciones' => [
                'A' => 'La incapacidad de redactar una buena solicitud formal suele reducir las perspectivas de empleo.',
                'B' => 'El único propósito de la educación superior es la redacción epistolar.',
                'C' => 'El éxito vocacional no depende en modo alguno de las solicitudes presentadas.',
                'D' => 'Los colegios secundarios reemplazan a las universidades en formación práctica.',
                'E' => 'Los educadores no requieren instruir en comunicación formal a sus egresados.',
            ],
        ],
        9 => [
            'enunciado' => "PASAJE IX\n«Cuello blanco» es un término utilizado para describir a uno de los grupos más grandes de trabajadores de la industria y el comercio. Distingue a los que trabajan con la pluma y la mente de aquellos que lo hacen con las manos y la máquina. Sugiere ocupaciones en las que el esfuerzo físico y el manejo de materiales no son rasgos primordiales del trabajo.\n\n¿Qué caracteriza al segmento laboral clasificado como «cuello blanco»?",
            'clave' => 'D',
            'opciones' => [
                'A' => 'Poseen menor resistencia física frente a cualquier tarea manual.',
                'B' => 'Se dedican con exclusividad a supervisar depósitos de insumos.',
                'C' => 'Tienen prohibido desempeñar funciones fuera de recintos cerrados.',
                'D' => 'Su actividad principal no exige el manejo rudo de maquinaria ni el esfuerzo muscular preponderante.',
                'E' => 'Pertenecen únicamente al estrato directivo de corporaciones multinacionales.',
            ],
        ],
        10 => [
            'enunciado' => "PASAJE X\nLas tasas vibratorias percibidas por los oídos como tonos musicales tienen unos límites bien definidos. En el oído, al igual que en el ojo, hay variaciones individuales. Sin embargo, las variaciones están más marcadas en el oído, pues su gama perceptiva es mayor.\n\n¿Qué diferencia al sentido auditivo del sentido visual según el pasaje?",
            'clave' => 'C',
            'opciones' => [
                'A' => 'El oído está condicionado por la rigidez de su espectro armónico.',
                'B' => 'El tímpano carece de variabilidad entre un individuo y otro.',
                'C' => 'El oído presenta una gama perceptiva más amplia y con variaciones individuales más marcadas que el ojo.',
                'D' => 'El ojo tiene mayor capacidad de adaptación sonora que los órganos auditivos.',
                'E' => 'La vista percibe frecuencias vibratorias que el oído ignora por completo.',
            ],
        ],
        11 => [
            'enunciado' => "PASAJE XI\nAtenienses, escuchadme sin interrupciones; ha habido un gran acuerdo entre nosotros que debéis conocer por mí. Habéis de saber que, si matáis a alguien como yo, os haríais más daño a vosotros que a mí mismo... Pues si me matáis no os será fácil encontrar otro hombre como yo, quien, si se me permite utilizar esta figura, soy una especie de tábano que Dios ha regalado al Estado; y el Estado es como un corcel noble pero tardo en sus movimientos por causa de su tamaño, y requiere ser espoleado para que se mueva...\n\n¿Quién es probablemente el emisor del discurso en este pasaje?",
            'clave' => 'D',
            'opciones' => [
                'A' => 'Un cónsul romano dirigiéndose al senado de la república.',
                'B' => 'Un militar espartano arengando a sus tropas aliadas.',
                'C' => 'Un magistrado judicial leyendo una sentencia condenatoria.',
                'D' => 'Un filósofo griego compareciendo ante la asamblea ateniense.',
                'E' => 'Un orador político defendiendo una reforma tributaria.',
            ],
        ],
        12 => [
            'enunciado' => "PASAJE XI (Continuación)\n«Dios me ha regalado al Estado como tábano a un corcel noble pero tardo en sus movimientos por su tamaño, que requiere ser espoleado para que se mueva. Todo el día y en todos los lugares estoy exhortándote, persuadiéndote y reprochándote a considerar la virtud...»\n\n¿Según la metáfora del pasaje, con qué propósito fue enviado el 'tábano'?",
            'clave' => 'C',
            'opciones' => [
                'A' => 'Incomodar maliciosamente la paz y tranquilidad ciudadana.',
                'B' => 'Frenar el crecimiento económico y militar de la polis.',
                'C' => 'Provocar y despertar al pueblo hacia una acción constructiva y virtuosa.',
                'D' => 'Sembrar dudas que debiliten las leyes tradicionales del Estado.',
                'E' => 'Provocar castigos severos contra los gobernantes atenienses.',
            ],
        ],
        13 => [
            'enunciado' => "PASAJE XI (Continuación)\n«Y que Dios me ha entregado a ti se demuestra con esto: no habría desatendido mis intereses ni visto pacientemente el desprecio de ellos durante todos estos años para exhortaros a considerar la virtud... Ni he buscado el pago de nadie: mi pobreza es testigo suficiente.»\n\n¿Qué pide y reclama esencialmente el orador en su defensa?",
            'clave' => 'E',
            'opciones' => [
                'A' => 'Clemencia incondicional y exoneración de impuestos.',
                'B' => 'Retirarse con una pensión honorífica del tesoro ateniense.',
                'C' => 'Venganza contra sus acusadores Meleto y Ánito.',
                'D' => 'El destierro hacia tierras lejanas para no ser molestado.',
                'E' => 'Ser oído y el derecho a continuar su labor de exhortar y filosofar.',
            ],
        ],
        14 => [
            'enunciado' => "PASAJE XI (Continuación)\n«Me atrevo a decir que puedes sentirte irritado, como quien es despertado de repente de su siesta, y pensar que si me condenaran a muerte podrías dormir fácilmente el resto de tu vida, a menos que Dios se cuidara de darte otro tábano...»\n\n¿Por qué advierte el hablante que no deberían quitarle la vida?",
            'clave' => 'D',
            'opciones' => [
                'A' => 'Porque sus familiares desatarán una rebelión civil en Atenas.',
                'B' => 'Porque el Estado caerá en bancarrota sin su fortuna personal.',
                'C' => 'Porque sus enseñanzas militares son indispensables en caso de guerra.',
                'D' => 'Porque difícilmente encontrarán a otro hombre como él dedicado enteramente a su despertar moral.',
                'E' => 'Porque los dioses destruirán los templos si se derrama sangre injusta.',
            ],
        ],
        15 => [
            'enunciado' => "PASAJE XI (Conclusión)\n«Si hubiera ganado algo o mis exhortaciones hubieran sido pagadas, habría habido algún sentido en ello; pero como os dais cuenta, mis acusadores no tienen testigos de que haya exigido o buscado pago; y yo tengo un testigo de la verdad de lo que digo: mi pobreza es un testigo suficiente.»\n\n¿Qué evidencia presenta el hablante para demostrar la legitimidad y desinterés de su misión?",
            'clave' => 'D',
            'opciones' => [
                'A' => 'Documentos notariales firmados por los jueces de la corte.',
                'B' => 'El testimonio jurado de los comerciantes más acaudalados de Atenas.',
                'C' => 'Los tributos y donaciones recibidos en los templos del Ágora.',
                'D' => 'Su evidente estado de pobreza, prueba incontestable de no haber cobrado jamás por enseñar.',
                'E' => 'El respaldo expreso del ejército y los generales atenienses.',
            ],
        ],
        16 => [
            'enunciado' => "PASAJE XII\nConsiderando que una gran parte de los territorios que, por el presente tratado, estarán en el futuro dentro de las fronteras de los Estados Unidos, están ocupados ahora por tribus salvajes, que a partir de ahora quedarán bajo el control exclusivo del Gobierno de los Estados Unidos, y cuyas incursiones en territorio mexicano serían perjudiciales en extremo, se acepta solemnemente que todas esas incursiones sean evitadas por la fuerza por el Gobierno de los Estados Unidos siempre que sea necesario...\n\n¿Qué tipo de ley se cita o refiere en este pasaje?",
            'clave' => 'B',
            'opciones' => [
                'A' => 'Ley estatal.',
                'B' => 'Ley federal.',
                'C' => 'Ley municipal.',
                'D' => 'Ley de condado.',
                'E' => 'Ley estatutaria.',
            ],
        ],
        17 => [
            'enunciado' => "PASAJE XII (Continuación)\n«No será legal, bajo ningún pretexto, que un habitante de los Estados Unidos compre o adquiera a un mexicano, o un extranjero residente en México, que haya sido capturado por los indios que habitan el territorio de las dos repúblicas; ni que compre o adquiera caballos, mulas, ganado ni propiedades de ningún tipo que hayan sido robadas por esos indios en territorio mexicano.»\n\nSegún este artículo, ¿qué acción es terminantemente ilegal?",
            'clave' => 'D',
            'opciones' => [
                'A' => 'Comprar bienes robados en subastas autorizadas.',
                'B' => 'Comprar a cualquier nativo como siervo doméstico.',
                'C' => 'Comprar un caballo a un poblador tejano.',
                'D' => 'Comprar un prisionero o bienes robados procedentes de incursiones ilegales.',
                'E' => 'Comprar propiedades a un ciudadano en Acapulco.',
            ],
        ],
        18 => [
            'enunciado' => "PASAJE XII (Continuación)\n«...Y en el caso de que una persona o personas, capturadas dentro de territorio mexicano por los indios, sean llevadas a territorio de los Estados Unidos, su gobierno se compromete, de la manera más solemne, tan pronto como sepa que esos cautivos están en su territorio, y así pueda hacerlo mediante el fiel ejercicio de su influencia y poder, a rescatarlos y devolverlos a su país, o entregarlos a un agente o representante del Gobierno mexicano...»\n\n¿De qué manera ha de aplicarse el castigo o control de las tribus que realicen incursiones en México?",
            'clave' => 'D',
            'opciones' => [
                'A' => 'Ha de ser ejecutado conjuntamente por tribunales civiles mexicanos.',
                'B' => 'Ha de ser limitado y moderado unilateralmente por las autoridades locales.',
                'C' => 'Puede ser suspendido a discreción de los pobladores fronterizos.',
                'D' => 'No se detalla el método de castigo punitivo específico en este pasaje.',
                'E' => 'Depende de la nacionalidad y linaje del cautivo recuperado.',
            ],
        ],
        19 => [
            'enunciado' => "PASAJE XII (Continuación)\n«A fin de dar a estas estipulaciones la máxima eficacia posible, para permitir la seguridad y reparación exigidas por su verdadero espíritu e intención, el Gobierno de los Estados Unidos, a partir de ahora, sacará sin retrasos innecesarios, y obligará a cumplir, las leyes que la naturaleza del tema exija...»\n\nPara garantizar la eficacia de las estipulaciones acordadas, ¿qué medida específica asume el gobierno estadounidense?",
            'clave' => 'E',
            'opciones' => [
                'A' => 'Bloquearán la ejecución de leyes ordinarias que no tengan carácter militar.',
                'B' => 'Llevarán a cabo invasiones preventivas en territorio limítrofe.',
                'C' => 'Eliminarán a las tribus de todas las regiones colonizadas.',
                'D' => 'Cruzarán libremente la frontera con tropas sin aviso previo.',
                'E' => 'Redactarán con prontitud y harán cumplir las leyes que exija la naturaleza del problema.',
            ],
        ],
        20 => [
            'enunciado' => "PASAJE XII (Conclusión)\n«...Y, finalmente, esta obligación sagrada nunca será olvidada por el mencionado gobierno, cuando proceda a la colonización del mencionado territorio por ciudadanos de los Estados Unidos; antes al contrario, se ocupará especialmente de no poner a sus ocupantes en la necesidad de buscar un nuevo hogar, cometiendo las invasiones que los Estados Unidos se han comprometido solemnemente a limitar.»\n\nDe acuerdo con su contenido y contexto histórico, ¿en qué período y circunstancias fue redactado este tratado?",
            'clave' => 'C',
            'opciones' => [
                'A' => 'En una época en que ambos países sostenían una guerra activa y abierta.',
                'B' => 'Durante una breve tregua comercial en el Golfo de México.',
                'C' => 'En una época de transición donde territorios bajo soberanía mexicana pasaban a jurisdicción de los Estados Unidos.',
                'D' => 'Cuando los Estados Unidos se disponían a devolver posesiones a México.',
                'E' => 'En un acuerdo de intercambio de islas caribeñas.',
            ],
        ],
    ];

    public function run(): void
    {
        $prueba = Prueba::query()->where('codigo', 'TEST_LECTURA_COMPRENSIVA')->firstOrFail();

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

        $this->command?->info('¡20 preguntas oficiales de Habilidades y Aptitudes Específicas (100% del test) registradas con éxito!');
    }
}

