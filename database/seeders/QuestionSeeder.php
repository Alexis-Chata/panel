<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // =========
        // 1) LOTE BASE (15 preguntas de cultura general)
        // =========
        $base =
            [
                [
                    'statement' => '¿Cuántos Huesos tiene el ser Humano Adulto?',
                    'feedback'  => 'El cuerpo humano adulto tiene 206 huesos, ya que varios huesos que están separados al nacer se fusionan con el crecimiento.',
                    'correct'   => 'B',
                    'A' => '302',
                    'B' => '206',
                    'C' => '208',
                    'D' => '306',
                ],
                [
                    'statement' => '¿Cuántos Huesos tiene un bebe al nacer?',
                    'feedback'  => 'Un bebé nace con alrededor de 300 huesos, pero muchos se van uniendo con el desarrollo hasta quedar en 206 en la adultez.',
                    'correct'   => 'A',
                    'A' => '300',
                    'B' => '206',
                    'C' => '196',
                    'D' => '192',
                ],
                [
                    'statement' => '¿Cuántos océanos hay en el planeta tierra?',
                    'feedback'  => 'Actualmente se reconocen 5 océanos: Pacífico, Atlántico, Índico, Ártico y Antártico.',
                    'correct'   => 'C',
                    'A' => '6',
                    'B' => '4',
                    'C' => '5',
                    'D' => '7',
                ],
                [
                    'statement' => '¿Cuál es la estrella más cercana a la tierra?',
                    'feedback'  => 'El Sol es la estrella más cercana a la Tierra y la fuente principal de luz y energía para la vida.',
                    'correct'   => 'B',
                    'A' => 'Andromeda',
                    'B' => 'Sol',
                    'C' => 'Luna',
                    'D' => 'Ninguno',
                ],
                [
                    'statement' => '¿Cuántos Kilos hay en una Tonelada?',
                    'feedback'  => 'Una tonelada equivale a 1.000 kilogramos.',
                    'correct'   => 'A',
                    'A' => '1.000',
                    'B' => '100',
                    'C' => '10.000',
                    'D' => '1.500',
                ],
                [
                    'statement' => '¿Cuál es el océano más grande?',
                    'feedback'  => 'El océano Pacífico es el más grande del planeta, cubriendo más superficie que todos los continentes juntos.',
                    'correct'   => 'A',
                    'A' => 'Pacífico',
                    'B' => 'Atlántico',
                    'C' => 'Índico',
                    'D' => 'Ártico',
                ],
                [
                    'statement' => '¿Cuál es el planeta más cercano al Sol?',
                    'feedback'  => 'Mercurio es el planeta más cercano al Sol y también el más pequeño del sistema solar.',
                    'correct'   => 'D',
                    'A' => 'Venus',
                    'B' => 'Tierra',
                    'C' => 'Marte',
                    'D' => 'Mercurio',
                ],
                [
                    'statement' => '¿Cuál es el nombre de la galaxia donde vivimos?',
                    'feedback'  => 'Vivimos en la galaxia llamada Vía Láctea, donde también se encuentra nuestro sistema solar.',
                    'correct'   => 'C',
                    'A' => 'Andromeda',
                    'B' => 'Pegaso',
                    'C' => 'Vía Láctea',
                    'D' => 'Osa Mayor',
                ],
                [
                    'statement' => '¿Quién pintó “La Gioconda”?',
                    'feedback'  => 'La Gioconda, también conocida como Mona Lisa, fue pintada por Leonardo da Vinci en el siglo XVI.',
                    'correct'   => 'A',
                    'A' => 'Leonardo da Vinci',
                    'B' => 'Miguel Ángel',
                    'C' => 'Rafael',
                    'D' => 'Botticelli',
                ],
                [
                    'statement' => '¿Cómo se llama el movimiento que realiza el Corazón”?',
                    'feedback'  => 'es Latido',
                    'correct'   => 'C',
                    'A' => 'Sistole',
                    'B' => 'Diástole',
                    'C' => 'Latido',
                    'D' => 'pam pam',
                ],

                [
                    'statement' => '¿Qué alimiento nunca se pudre”?',
                    'feedback'  => 'La miel no se pudre debido a su bajo contenido de agua, su pH ácido y la presencia de peróxido de hidrógeno.',
                    'correct'   => 'C',
                    'A' => 'Huevos',
                    'B' => 'Plátano',
                    'C' => 'Miel',
                    'D' => 'Palta',
                ],
                [
                    'statement' => '¿Qué es el tomate”?',
                    'feedback'  => 'Sí, el tomate es una fruta desde el punto de vista botánico, ya que se desarrolla a partir de la flor de la planta y contiene semillas.',
                    'correct'   => 'B',
                    'A' => 'Legumbre',
                    'B' => 'Fruta',
                    'C' => 'Vegetal',
                    'D' => 'Ninguna',
                ],
                [
                    'statement' => '¿Cuántos corazones tiene un pulpo”?',
                    'feedback'  => 'El pulpo tiene tres corazones: dos corazones branquiales que bombean sangre a las branquias para su oxigenación, y un corazón sistémico que distribuye la sangre oxigenada por todo el cuerpo. ',
                    'correct'   => 'B',
                    'A' => '2',
                    'B' => '3',
                    'C' => '1',
                    'D' => '4',
                ],

                [
                    'statement' => '¿Quién escribió "Don Quijote de la Mancha"?',
                    'feedback'  => 'La novela cumbre del Siglo de Oro fue escrita por Miguel de Cervantes y publicada en dos partes (1605 y 1615).',
                    'correct'   => 'B',
                    'A' => 'Lope de Vega',
                    'B' => 'Miguel de Cervantes',
                    'C' => 'Garcilaso de la Vega',
                    'D' => 'Francisco de Quevedo',
                ],
                [
                    'statement' => '¿Quién recibió el Premio Nobel de Literatura en 2010?',
                    'feedback'  => 'El escritor peruano Mario Vargas Llosa obtuvo el Nobel en 2010.',
                    'correct'   => 'B',
                    'A' => 'Gabriel García Márquez',
                    'B' => 'Mario Vargas Llosa',
                    'C' => 'Octavio Paz',
                    'D' => 'José Saramago',
                ],
                [
                    'statement' => '¿A qué movimiento literario se asocia "Cien años de soledad"?',
                    'feedback'  => 'La obra de García Márquez es emblema del realismo mágico.',
                    'correct'   => 'A',
                    'A' => 'Realismo mágico',
                    'B' => 'Existencialismo',
                    'C' => 'Vanguardismo',
                    'D' => 'Romanticismo',
                ],
                [
                    'statement' => '¿Quién es el autor de "La Odisea"?',
                    'feedback'  => 'Tradicionalmente se atribuye a Homero, poeta griego de la Antigüedad.',
                    'correct'   => 'B',
                    'A' => 'Virgilio',
                    'B' => 'Homero',
                    'C' => 'Esquilo',
                    'D' => 'Sófocles',
                ],
                [
                    'statement' => '¿De qué país es el escritor Jorge Luis Borges?',
                    'feedback'  => 'Borges nació en Buenos Aires, Argentina, y es figura clave de la literatura universal.',
                    'correct'   => 'A',
                    'A' => 'Argentina',
                    'B' => 'México',
                    'C' => 'España',
                    'D' => 'Chile',
                ],
                [
                    'statement' => '¿Quién escribió "La metamorfosis"?',
                    'feedback'  => 'Franz Kafka publicó este clásico en 1915.',
                    'correct'   => 'A',
                    'A' => 'Franz Kafka',
                    'B' => 'Albert Camus',
                    'C' => 'Fiódor Dostoievski',
                    'D' => 'Thomas Mann',
                ],
                [
                    'statement' => '¿A qué tradición teatral pertenece "Romeo y Julieta"?',
                    'feedback'  => 'La tragedia de Shakespeare forma parte del teatro isabelino inglés.',
                    'correct'   => 'A',
                    'A' => 'Teatro isabelino',
                    'B' => 'Teatro clásico griego',
                    'C' => 'Commedia dell’arte',
                    'D' => 'Siglo de Oro español',
                ],
                [
                    'statement' => '¿En qué idioma fue escrita originalmente "La Divina Comedia"?',
                    'feedback'  => 'Dante la escribió en italiano toscano, no en latín.',
                    'correct'   => 'B',
                    'A' => 'Latín',
                    'B' => 'Italiano',
                    'C' => 'Francés',
                    'D' => 'Español',
                ],
                [
                    'statement' => '¿Qué tipo de obra es "El principito"?',
                    'feedback'  => 'Es un cuento largo o novela breve de tono poético y filosófico.',
                    'correct'   => 'B',
                    'A' => 'Ensayo',
                    'B' => 'Cuento largo / novela breve',
                    'C' => 'Obra de teatro',
                    'D' => 'Poemario',
                ],
                [
                    'statement' => '¿Qué rasgo distingue a "Rayuela" de Julio Cortázar?',
                    'feedback'  => 'Propone una lectura no lineal con distintos itinerarios.',
                    'correct'   => 'B',
                    'A' => 'Es una novela lineal',
                    'B' => 'Ofrece una lectura no lineal',
                    'C' => 'Es una epopeya',
                    'D' => 'Es una tragedia clásica',
                ],
                [
                    'statement' => '¿Dónde se ambienta "El nombre de la rosa" de Umberto Eco?',
                    'feedback'  => 'La trama ocurre en una abadía medieval benedictina.',
                    'correct'   => 'A',
                    'A' => 'Una abadía medieval',
                    'B' => 'París del siglo XIX',
                    'C' => 'La Roma imperial',
                    'D' => 'El Londres victoriano',
                ],
                [
                    'statement' => '¿En qué década se consolidó el “Boom Latinoamericano”?',
                    'feedback'  => 'El Boom se asocia principalmente a la década de 1960.',
                    'correct'   => 'B',
                    'A' => '1920',
                    'B' => '1960',
                    'C' => '1890',
                    'D' => '2000',
                ],
                [
                    'statement' => '¿Quién es el autor de "Don Juan Tenorio"?',
                    'feedback'  => 'La obra romántica más famosa sobre Don Juan es de José Zorrilla.',
                    'correct'   => 'A',
                    'A' => 'José Zorrilla',
                    'B' => 'Calderón de la Barca',
                    'C' => 'Tirso de Molina',
                    'D' => 'Sor Juana Inés de la Cruz',
                ],
                [
                    'statement' => '¿Cuál es el tema central de "1984" de George Orwell?',
                    'feedback'  => 'Explora el totalitarismo, la vigilancia y la manipulación del lenguaje.',
                    'correct'   => 'B',
                    'A' => 'Utopía tecnológica',
                    'B' => 'Totalitarismo y vigilancia',
                    'C' => 'Romance cortesano',
                    'D' => 'Mitología nórdica',
                ],
                [
                    'statement' => '¿Quién escribió "La casa de Bernarda Alba"?',
                    'feedback'  => 'La obra teatral es de Federico García Lorca (1936).',
                    'correct'   => 'C',
                    'A' => 'Gabriela Mistral',
                    'B' => 'Emilia Pardo Bazán',
                    'C' => 'Federico García Lorca',
                    'D' => 'Alfonsina Storni',
                ],
            ];


        foreach ($base as $row) {
            $this->storeQuestion($row);
        }
    }

    /** Guarda una pregunta con sus 4 opciones (A–D), idempotente por 'statement'. */
    private function storeQuestion(array $row): void
    {
        $q = Question::firstOrCreate(
            ['statement' => $row['statement']],
            ['feedback'  => $row['feedback'] ?? null, 'question_group_id' => 1],
        );

        // Reescribir opciones para asegurar exactamente 1 correcta
        $q->options()->delete();

        $map = ['A', 'B', 'C', 'D'];
        $order = 1;
        foreach ($map as $label) {
            QuestionOption::create([
                'question_id' => $q->id,
                'label'       => $label,
                'content'     => (string)$row[$label],
                'is_correct'  => ($row['correct'] === $label),
                'opt_order'   => $order++,
            ]);
        }
    }

    /** Toma 3 capitales distintas a la correcta. */
    private function pickThreeDistinct(array $pool, string $exclude): array
    {
        $candidates = array_values(array_filter($pool, fn($x) => $x !== $exclude));
        shuffle($candidates);
        return array_slice($candidates, 0, 3);
    }

    /** Genera 3 distractores numéricos cercanos, únicos y positivos. */
    private function nearbyDistractors(int $correct): array
    {
        $alts = [];
        $deltas = [-6, -4, -3, -2, 2, 3, 4, 6, 8, 10];
        shuffle($deltas);
        foreach ($deltas as $d) {
            $v = $correct + $d;
            if ($v > 0 && $v !== $correct && !in_array($v, $alts, true)) {
                $alts[] = $v;
            }
            if (count($alts) === 3) break;
        }
        // Fallback por si acaso
        while (count($alts) < 3) {
            $alts[] = max(1, $correct + count($alts) + 2);
        }
        return $alts;
    }
}
