<?php

namespace Database\Seeders;

use App\Models\DimensionEvaluada;
use App\Models\Pregunta;
use App\Models\Prueba;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Forma B paralela: mismos factores A–Q4 que la forma A, con enunciados distintos.
 */
class BancoPreguntas16PfFormaBSeeder extends Seeder
{
    public function run(): void
    {
        $formaA = Prueba::query()->where('codigo', '16PF_FORMA_A')->first();
        $formaB = Prueba::query()->where('codigo', '16PF_FORMA_B')->first();

        if ($formaA === null || $formaB === null) {
            return;
        }

        $origen = Pregunta::query()
            ->where('prueba_id', $formaA->id)
            ->with('dimension:id,codigo')
            ->orderBy('orden')
            ->get();

        if ($origen->count() !== 187) {
            $this->command?->warn('La forma A no tiene 187 preguntas. No se armó la forma B.');

            return;
        }

        $banco = self::enunciados();
        $usados = [];

        DB::transaction(function () use ($formaB, $origen, $banco, &$usados): void {
            $ids = Pregunta::query()->where('prueba_id', $formaB->id)->pluck('id');
            if ($ids->isNotEmpty()) {
                DB::table('talento.respuesta_intento')->whereIn('pregunta_id', $ids)->delete();
                Pregunta::query()->where('prueba_id', $formaB->id)->delete();
            }

            $dimensiones = DimensionEvaluada::query()->pluck('id', 'codigo');

            foreach ($origen as $pregunta) {
                $codigo = $pregunta->dimension?->codigo;
                if ($codigo === null || ! isset($banco[$codigo])) {
                    throw new RuntimeException("La forma A tiene una pregunta sin factor reconocible (orden {$pregunta->orden}).");
                }

                $indice = $usados[$codigo] ?? 0;
                $enunciado = $banco[$codigo][$indice] ?? null;
                if ($enunciado === null) {
                    throw new RuntimeException("Faltan enunciados paralelos para el factor {$codigo}.");
                }
                $usados[$codigo] = $indice + 1;

                $nueva = Pregunta::query()->create([
                    'prueba_id' => $formaB->id,
                    'tipo_prueba' => 'TEST',
                    'dimension_id' => $dimensiones[$codigo],
                    'orden' => $pregunta->orden,
                    'enunciado' => $enunciado,
                ]);

                $nueva->opciones()->createMany([
                    ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2],
                    ['etiqueta' => 'B', 'texto' => 'Término medio', 'puntaje' => 1],
                    ['etiqueta' => 'C', 'texto' => 'No', 'puntaje' => 0],
                ]);
            }
        });

        $this->command?->info('Forma B lista: 187 preguntas paralelas en los factores A–Q4.');
    }

    /**
     * @return array<string, list<string>>
     */
    public static function enunciados(): array
    {
        return [
            'SOCIABILIDAD' => [
                'En un descanso busco sumarme a la conversación del grupo.',
                'Me agrada que en casa haya visitas con frecuencia.',
                'Cuando llego a un lugar nuevo, saludo primero.',
                'Prefiero almorzar acompañado antes que solo.',
                'Me resulta fácil retomar el contacto con alguien que no veo hace tiempo.',
                'Disfruto los encargos en los que tengo que hablar con varias personas.',
                'En una fila o una sala de espera termino charlando con quien está al lado.',
                'Me gusta que los fines de semana incluyan un plan con más gente.',
                'Si un compañero está callado, le abro tema de conversación.',
                'Prefiero un trabajo con trato cotidiano antes que uno aislado.',
                'En una celebración me muevo de un grupo a otro.',
                'Me siento a gusto presentando a dos personas que no se conocen.',
                'Después de una reunión, suelo seguir la charla un rato más.',
                'Elijo asientos donde pueda estar cerca de los demás.',
            ],
            'CAPACIDAD_ESCOLAR' => [
                'Entiendo con rapidez una explicación que usa conceptos abstractos.',
                'Me interesa saber el porqué de una regla, no solo aplicarla.',
                'Puedo seguir un razonamiento largo sin perder el hilo.',
                'Aprendo mejor cuando el tema exige relacionar ideas.',
                'Me resulta sencillo traducir un problema a un esquema.',
                'Disfruto las lecturas que exigen interpretar, no memorizar.',
                'Detecto rápido una contradicción en un argumento.',
                'Puedo explicar con mis palabras una idea que acabo de leer.',
                'Me atraen los problemas que no tienen un único camino.',
                'Retengo la lógica de un procedimiento después de verlo una vez.',
                'Comparo alternativas antes de quedarme con la primera.',
                'Me cuesta poco pasar de un ejemplo concreto a la regla general.',
                'Cuando estudio, busco el principio que une los casos.',
                'Resuelvo mejor las tareas que piden deducir que las que piden repetir.',
                'Distingo la idea principal de los ejemplos que solo la ilustran.',
                'Puedo reordenar datos sueltos hasta ver la estructura.',
                'Una definición breve me alcanza para reconocer casos nuevos.',
                'Sigo un texto denso sin necesidad de que me lo repitan.',
            ],
            'ESTABILIDAD_EMOCIONAL' => [
                'Si el plan del día se desarma, recupero la calma en poco tiempo.',
                'Una crítica inesperada no me desordena el resto de la jornada.',
                'Puedo decidir con la cabeza fría aunque esté cansado.',
                'Cuando algo sale mal, no me quedo dando vueltas toda la tarde.',
                'Mantengo un trato parejo aunque el entorno esté tenso.',
                'Un contratiempo pequeño no me cambia el humor del día.',
                'Después de una discusión, vuelvo a trabajar sin arrastrar el enojo.',
                'Duermo bien aunque tenga un pendiente importante.',
                'Tolero la espera sin irritarme.',
                'Si cometo un error visible, lo corrijo sin derrumbarme.',
                'Puedo escuchar una queja sin sentirme atacado de inmediato.',
                'En una semana cargada sigo respondiendo con serenidad.',
                'No necesito que todo salga perfecto para quedar tranquilo.',
                'Cuando hay prisa, mi voz y mis gestos se mantienen estables.',
            ],
            'SUMISION' => [
                'Si no estoy de acuerdo, lo digo aunque el otro tenga más autoridad.',
                'En un grupo tomo la palabra para marcar el rumbo.',
                'Defiendo mi criterio cuando alguien intenta imponer el suyo.',
                'Me siento cómodo dirigiendo una tarea compartida.',
                'Prefiero proponer el plan antes que esperar a que me lo asignen.',
                'Corrijo un error del equipo aunque eso genere discusión.',
                'No cedo solo por evitar el conflicto.',
                'Cuando hay que decidir ya, asumo la decisión.',
                'Me resulta natural pedir que se cumpla lo acordado.',
                'En una negociación sostengo lo que considero justo.',
                'Organizo el trabajo de otros sin incomodarme.',
                'Si una indicación me parece mal, pido que se revise.',
                'Prefiero liderar la exposición antes que quedarme en segunda fila.',
                'Digo que no cuando un pedido choca con lo que ya comprometí.',
            ],
            'RETRAIMIENTO' => [
                'Las actividades con movimiento me entusiasman más que las quietas.',
                'Me animo rápido cuando proponen un plan improvisado.',
                'Hablo con energía cuando el tema me importa.',
                'Busco variedad: me aburre repetir siempre la misma rutina.',
                'En una salida prefiero algo animado antes que una velada silenciosa.',
                'Rio y comento con soltura cuando el ambiente está suelto.',
                'Me levanta el ánimo cambiar de escena durante el día.',
                'Cuento lo que me pasó con ganas, no solo por compromiso.',
                'Prefiero un ritmo vivo de trabajo, con varias cosas en marcha.',
                'Me sumo a un juego o una dinámica sin que insistan.',
                'Una buena noticia me dura y la comparto.',
                'Me gusta empezar la semana con algo distinto por hacer.',
                'Cuando viajo, lleno el día en lugar de quedarme en el cuarto.',
                'El silencio largo me pesa: prefiero que haya intercambio.',
            ],
            'SUPEREGO' => [
                'Cumplo un plazo aunque nadie me lo vaya a controlar.',
                'Devuelvo lo que no es mío aunque pueda quedármelo sin que se note.',
                'Reviso un trabajo hasta dejarlo como se pidió.',
                'Llego a la hora aunque el otro suela retrasarse.',
                'Sigo una norma del lugar aunque no me resulte cómoda.',
                'Admito un error mío antes de que lo descubran.',
                'Termino lo que empecé, incluso la parte aburrida.',
                'No firmo algo que no revisé.',
                'Cuido el material compartido como si fuera propio.',
                'Aviso si voy a incumplir, en lugar de desaparecer.',
                'Prefiero hacer lo correcto aunque tarde más.',
                'Mantengo el mismo criterio cuando nadie mira.',
                'Anoto los pendientes para no dejarlos a medias.',
                'Me molesta entregar algo que sé que está incompleto.',
                'Cumplo un encargo incómodo si ya di mi palabra.',
                'Separo lo urgente de lo que puede esperar y respeto ese orden.',
            ],
            'TIMIDEZ' => [
                'Puedo hablar ante un grupo sin prepararme durante horas.',
                'Me presento a alguien de mayor jerarquía sin quedarme callado.',
                'Si no entiendo, pregunto en voz alta.',
                'Acepto un reto nuevo aunque no lo haya hecho antes.',
                'Entro a una sala llena y busco mi lugar sin dudar.',
                'Sostengo la mirada cuando me dirijo a varias personas.',
                'Improviso una respuesta si me preguntan de sorpresa.',
                'Me ofrezco voluntario cuando piden a alguien del grupo.',
                'Una situación social nueva me activa, no me frena.',
                'Puedo defender una idea en público sin que me tiemble la voz.',
                'Llamo por teléfono a un desconocido si la tarea lo pide.',
                'No espero a que otro hable por mí.',
                'Pruebo una actividad aunque pueda equivocarme delante de otros.',
                'Saludo primero a un visitante que llega al equipo.',
            ],
            'SEVERIDAD' => [
                'Decido con datos, aunque la opción fría no sea la más simpática.',
                'En un desacuerdo me importa más el resultado que el tono emocional.',
                'Prefiero una solución práctica antes que una muy elaborada en lo sentimental.',
                'Puedo dar una noticia dura de forma directa.',
                'Valoro que un plan funcione, aunque no quede elegante.',
                'No cambio una regla solo porque alguien se sienta incómodo.',
                'Cuando ayudo, miro qué resuelve el problema y no solo cómo se siente el otro.',
                'Me cuesta poco dejar de lado un gusto personal si estorba la tarea.',
                'Juzgo un trabajo por lo que produce, no por la intención.',
                'Prefiero instrucciones precisas antes que apelaciones emotivas.',
                'En una compra o un contrato miro el número antes que la historia.',
                'Puedo postergar un gusto inmediato si el cálculo dice que no conviene.',
                'Una discusión me resulta más clara cuando se habla de hechos.',
                'Elijo la herramienta que sirve, aunque no sea la que más me gusta.',
            ],
            'CONFIANZA' => [
                'Si alguien cambia su versión, dudo de lo que me dijo antes.',
                'Reviso dos veces un acuerdo antes de darlo por cerrado.',
                'No doy por cierta una promesa hasta ver el primer cumplimiento.',
                'Cuando un favor llega de golpe, me pregunto qué se espera a cambio.',
                'Observo si las palabras y los actos de una persona coinciden.',
                'Me reservo información hasta conocer mejor al interlocutor.',
                'Un elogio muy rápido me hace preguntarme qué lo motiva.',
                'Prefiero comprobar un dato yo mismo antes de repetirlo.',
                'Si un trámite se acelera sin explicación, pido el motivo.',
                'Tardo en confiar un encargo delicado a alguien recién llegado.',
                'Noto cuando una explicación deja huecos y los señalo.',
                'No comparto una clave o un acceso solo por amabilidad.',
                'Ante dos versiones, busco una tercera fuente.',
                'Me queda la duda si un silencio esconde un inconveniente.',
            ],
            'OBJETIVIDAD' => [
                'Se me ocurren usos distintos para un objeto común.',
                'Me distraigo imaginando cómo sería una escena que aún no existe.',
                'Prefiero una idea nueva, aunque todavía no tenga todos los detalles.',
                'Relaciono temas que para otros no tienen que ver.',
                'Me gusta pensar en posibilidades antes que en el procedimiento fijo.',
                'Una tarea rutinaria me deja espacio para imaginar otra forma de hacerla.',
                'Disfruto los problemas abiertos, sin una sola respuesta.',
                'Puedo describir una solución que todavía no se puede construir.',
                'Me atraen los proyectos donde hay que inventar el camino.',
                'Paso tiempo dándole vueltas a una idea solo por el gusto de explorarla.',
                'Veo imágenes o escenas mentales con bastante nitidez.',
                'Una restricción práctica no me impide proponer algo poco habitual.',
                'Prefiero bosquejar varias opciones antes de aterrizar una.',
                'Me interesa más la idea general que el detalle inmediato.',
            ],
            'INGENUIDAD' => [
                'Mido lo que digo cuando el tema puede usarse en mi contra.',
                'Puedo guardar un dato relevante hasta el momento conveniente.',
                'Noto qué le conviene escuchar a la otra persona y lo tengo en cuenta.',
                'No muestro todas mis cartas en la primera conversación.',
                'Ajusto el relato según quién me escucha, sin faltar a lo esencial.',
                'Detecto una intención detrás de una pregunta aparentemente simple.',
                'Prefiero no opinar de más en un grupo que no conozco.',
                'Puedo negociar dejando un margen sin decirlo explícitamente.',
                'Evito comprometerme en público hasta tener la información.',
                'Leo el ambiente antes de contar un problema personal.',
                'Sé callar un logro si contarlo me resta ventaja.',
                'Distingo un halago estratégico de un comentario espontáneo.',
                'Cuando explico un error, elijo qué detalle hace falta y cuál no.',
                'Mantengo una posición cordial mientras evalúo al otro.',
            ],
            'SEGURIDAD_DE_SI_MISMO' => [
                'Después de entregar un trabajo, sigo pensando en lo que pude hacer mal.',
                'Una observación menor me queda dando vueltas.',
                'Me cuesta conformarme: casi siempre veo un defecto propio.',
                'Antes de una exposición imagino que voy a olvidar algo importante.',
                'Comparo mi resultado con el de otros y suelo salir perdiendo en esa cuenta.',
                'Pido disculpas más de una vez por el mismo descuido.',
                'Un silencio del otro lo interpreto como señal de que no le gustó.',
                'Releo un mensaje varias veces por miedo a haber sonado mal.',
                'Me cuesta aceptar un elogio sin restarle valor.',
                'Si alguien se retrasa, pienso que hice algo que lo molestó.',
                'Dudo de una decisión correcta apenas aparece un inconveniente.',
                'Me preocupa haber quedado en ridículo aunque nadie lo diga.',
                'Repaso la jornada buscando el momento en que me equivoqué.',
                'Necesito que me confirmen que salió bien para quedar tranquilo.',
            ],
            'CONSERVADURISMO' => [
                'Pruebo una herramienta nueva aunque la anterior todavía sirva.',
                'Me interesa cambiar un método si hay uno más simple.',
                'Una costumbre del equipo no me impide proponer otra forma.',
                'Prefiero actualizarme antes que repetir el procedimiento de siempre.',
                'Acepto un cambio de plan si mejora el resultado.',
                'Me aburre hacer las cosas solo porque siempre se hicieron así.',
                'Busco otra vía cuando la regla actual se queda corta.',
                'Me gusta enterarme de ideas que contradicen lo que ya sé.',
                'Puedo dejar un hábito si encuentro uno que funciona mejor.',
                'En un proyecto nuevo no me ata la forma en que se hizo el anterior.',
                'Pregunto por qué se mantiene un paso que ya no aporta.',
                'Me atraen los encargos donde hay que rediseñar algo viejo.',
                'Cambio de opinión cuando aparecen datos que no tenía.',
                'Prefiero experimentar en pequeño antes que conservar todo igual.',
            ],
            'DEPENDENCIA_GRUPAL' => [
                'Puedo avanzar un tramo del trabajo sin consultar a cada paso.',
                'Prefiero armar mi propio método antes que copiar el del grupo.',
                'Me concentro mejor cuando decido solo el orden de las tareas.',
                'No necesito compañía para sostener una tarea larga.',
                'Si el grupo se estanca, sigo por mi cuenta un trecho.',
                'Tomo una decisión personal sin pedir aprobación de varios.',
                'Me siento cómodo siendo el único responsable de una parte.',
                'Organizo mi tiempo sin que alguien me lo marque.',
                'Puedo estudiar o trabajar un día entero en silencio.',
                'Cuando viajo o resuelvo un trámite, prefiero hacerlo solo.',
                'No me desordena quedar fuera de una charla del grupo.',
                'Confío en mi criterio antes de alinear cada detalle con los demás.',
                'Elijo un descanso solo si eso me repone más que uno compartido.',
                'Sostengo una meta propia aunque el entorno vaya hacia otro lado.',
            ],
            'INDIFERENCIA' => [
                'Releo un texto hasta que no quede un error evitable.',
                'Me molesta dejar una tarea a medias aunque el plazo lo permita.',
                'Ordeno el material antes de empezar, para no improvisar.',
                'Cuido que los detalles menores también queden bien.',
                'Corrijo un formato aunque el contenido ya esté aprobado.',
                'Prefiero tardar un poco más y entregar algo pulido.',
                'Llevo una lista y la voy tachando hasta el último punto.',
                'No me gusta mezclar papeles, archivos o versiones.',
                'Reviso la hora, el nombre y el número antes de enviar.',
                'Una presentación desprolija me resulta inaceptable.',
                'Planifico los pasos y me ciño a ese orden.',
                'Vuelvo sobre un trabajo ajeno si voy a firmarlo yo.',
                'Me incomoda cerrar el día con pendientes sueltos.',
                'Ajusto una y otra vez hasta que el resultado queda parejo.',
            ],
            'TRANQUILIDAD' => [
                'Cuando se acerca un plazo, siento el cuerpo en tensión.',
                'Me cuesta estar quieto si hay varias cosas sin resolver.',
                'Una espera larga me pone impaciente.',
                'Noto que apuro el habla cuando estoy presionado.',
                'Me cuesta desconectarme del trabajo al terminar el día.',
                'Los imprevistos me dejan con la energía alta durante un rato.',
                'Reviso la hora con frecuencia si tengo un compromiso.',
                'Me irrita que otros vayan más lento de lo que la tarea pide.',
                'Duermo peor si dejé algo importante abierto.',
                'Siento prisa aunque el plazo todavía alcance.',
                'Cambio de postura o camino si la reunión se alarga.',
                'Una crítica me activa de inmediato, no me deja indiferente.',
                'Empiezo la siguiente tarea antes de haber soltado la anterior.',
                'Me describe bien estar en marcha, con poca holgura.',
            ],
        ];
    }
}
