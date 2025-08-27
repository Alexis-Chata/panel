<?php

namespace App\Livewire\Forms;

use App\Models\GameSession;
use App\Models\GameSessionPool;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class GameSessionForm extends Form
{
    // Campos del formulario
    public string $title = '';
    public string $status = 'lobby';          // draft|lobby|phase1|phase2|phase3|results|finished
    public int $current_phase = 0;

    // Cantidades de preguntas por fase
    public ?int $phase1_count = null;
    public ?int $phase2_count = null;
    public ?int $phase3_count = null;

    public string $settings_json = '';
    public ?string $starts_at = null;         // datetime-local (string)
    public ?string $ends_at = null;           // datetime-local (string)
    public ?string $phase_ends_at = null;     // datetime-local (string)

    /**
     * pools por fase:
     * [
     *   1 => [ ['question_pool_id' => 3, 'weight' => 70], ['question_pool_id' => 9, 'weight' => 30] ],
     *   2 => [ ... ],
     *   3 => [ ... ],
     * ]
     */
    public array $pools = [
        1 => [],
        2 => [],
        3 => [],
    ];

    public array $settings = [
        1 => ['per_correct' => null],
        2 => ['per_correct' => null],
        3 => ['per_correct' => null, 'bonus_first' => null, 'bonus_second' => null, 'bonus_third' => null],
    ];


    protected function rules(): array
    {
        return [
            'title'          => ['required', 'string', 'max:255'],
            'status'         => ['required', Rule::in(['draft', 'lobby', 'phase1', 'phase2', 'phase3', 'results', 'finished'])],
            'current_phase'  => ['required', 'integer', 'min:0', 'max:3'],

            'phase1_count'   => ['nullable', 'integer', 'min:0'],
            'phase2_count'   => ['nullable', 'integer', 'min:0'],
            'phase3_count'   => ['nullable', 'integer', 'min:0'],

            // settings
            'settings.1.per_correct'   => ['nullable', 'integer', 'min:0'],
            'settings.2.per_correct'   => ['nullable', 'integer', 'min:0'],
            'settings.3.per_correct'   => ['nullable', 'integer', 'min:0'],
            'settings.3.bonus_first'   => ['nullable', 'integer', 'min:0'],
            'settings.3.bonus_second'  => ['nullable', 'integer', 'min:0'],
            'settings.3.bonus_third'   => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'pools.*.*.question_pool_id.required' => 'Seleccione un pool.',
            'pools.*.*.weight.required'           => 'Ingrese el peso.',
            'pools.*.*.weight.min'                => 'El peso debe ser al menos 1.',
            'pools.*.*.weight.max'                => 'El peso no puede exceder 100.',
        ];
    }

    public function addPoolRow(int $phase): void
    {
        $this->pools[$phase][] = ['question_pool_id' => null, 'weight' => null];
    }

    public function removePoolRow(int $phase, int $index): void
    {
        if (isset($this->pools[$phase][$index])) {
            array_splice($this->pools[$phase], $index, 1);
        }
    }

    public function resetToDefaults(): void
    {
        $this->title = '';
        $this->status = 'lobby';
        $this->current_phase = 0;

        $this->phase1_count = null;
        $this->phase2_count = null;
        $this->phase3_count = null;

        $this->settings_json = '';
        $this->starts_at = null;
        $this->ends_at = null;
        $this->phase_ends_at = null;

        $this->pools = [1 => [], 2 => [], 3 => []];
        $this->settings = [
            1 => ['per_correct' => null],
            2 => ['per_correct' => null],
            3 => ['per_correct' => null, 'bonus_first' => null, 'bonus_second' => null, 'bonus_third' => null],
        ];
    }

    public function store(): GameSession
    {
        // Validación base
        $validated = $this->validate();

        // Validar JSON si se envió
        $settings = [];
        if (trim($this->settings_json) !== '') {
            $decoded = json_decode($this->settings_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Lanzar error de validación amigable
                $this->addError('settings_json', 'El JSON no es válido.');
                $this->validate(); // muestra el error
            }
            $settings = $decoded ?: [];
        }

        // Reglas adicionales: suma de weights por fase y existencia si hay count>0
        /** @var Validator $postValidator */
        $postValidator = validator($validated, []);
        $postValidator->after(function ($v) {
            $counts = [
                1 => (int) ($this->phase1_count ?? 0),
                2 => (int) ($this->phase2_count ?? 0),
                3 => (int) ($this->phase3_count ?? 0),
            ];
            foreach ([1, 2, 3] as $phase) {
                $rows = $this->pools[$phase] ?? [];
                $sum  = 0;

                foreach ($rows as $i => $row) {
                    // evitar duplicidad de pool dentro de misma fase (opcional)
                    // podrías descomentar si lo quieres estricto:
                    // for ($j=$i+1; $j<count($rows); $j++) {
                    //     if ($row['question_pool_id'] && $row['question_pool_id'] == $rows[$j]['question_pool_id']) {
                    //         $v->errors()->add("pools.$phase.$i.question_pool_id", 'Pool repetido en la misma fase.');
                    //     }
                    // }

                    $sum += (int) ($row['weight'] ?? 0);
                }

                if ($counts[$phase] > 0) {
                    if (count($rows) === 0) {
                        $v->errors()->add("pools.$phase", "Debe agregar al menos un pool para la fase $phase.");
                    }
                    if ($sum !== 100) {
                        $v->errors()->add("pools.$phase", "La suma de pesos de la fase $phase debe ser 100 (actual: $sum).");
                    }
                } else {
                    // Si la fase no hará preguntas, permitimos tener 0 filas o, si las hay, también exigir 100.
                    if (count($rows) > 0 && $sum !== 100) {
                        $v->errors()->add("pools.$phase", "Si define pools en fase $phase, la suma de pesos debe ser 100 (actual: $sum).");
                    }
                }
            }
        });

        if ($postValidator->fails()) {
            foreach ($postValidator->errors()->messages() as $key => $msgs) {
                foreach ($msgs as $msg) {
                    $this->addError($key, $msg);
                }
            }
            $this->validate(); // para mostrar todo en la vista
        }

        // Generar código único
        do {
            $code = Str::upper(Str::random(6));
        } while (GameSession::where('code', $code)->exists());

        // Convertir datetime-local (string) a formato Y-m-d H:i:s (opcional, Eloquent castea a datetime)
        $startsAt    = $this->starts_at ? date('Y-m-d H:i:s', strtotime($this->starts_at)) : null;
        $endsAt      = $this->ends_at ? date('Y-m-d H:i:s', strtotime($this->ends_at)) : null;
        $phaseEndsAt = $this->phase_ends_at ? date('Y-m-d H:i:s', strtotime($this->phase_ends_at)) : null;

        // Crear sesión
        $session = GameSession::create([
            'code'           => $code,
            'title'          => $this->title,
            'status'         => $this->status,
            'current_phase'  => $this->current_phase,
            'phase1_count'   => $this->phase1_count,
            'phase2_count'   => $this->phase2_count,
            'phase3_count'   => $this->phase3_count,
            'settings_json'  => $this->settings,
            'starts_at'      => $startsAt,
            'ends_at'        => $endsAt,
            'phase_ends_at'  => $phaseEndsAt,
        ]);

        // Crear pools por fase
        foreach ([1, 2, 3] as $phase) {
            foreach ($this->pools[$phase] ?? [] as $row) {
                if (!empty($row['question_pool_id']) && !empty($row['weight'])) {
                    GameSessionPool::create([
                        'game_session_id'  => $session->id,
                        'question_pool_id' => (int) $row['question_pool_id'],
                        'phase'            => $phase,
                        'weight'           => (int) $row['weight'],
                    ]);
                }
            }
        }

        return $session;
    }
}
