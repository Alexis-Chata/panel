<?php

namespace App\Livewire\Forms;

use App\Models\GameSession;
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

    public ?int $phase1_count = null;
    public ?int $phase2_count = null;
    public ?int $phase3_count = null;

    public string $settings_json = '';
    public ?string $starts_at = null;         // datetime-local (string)
    public ?string $ends_at = null;           // datetime-local (string)
    public ?string $phase_ends_at = null;     // datetime-local (string)

    protected function rules(): array
    {
        return [
            'title'          => ['required', 'string', 'max:255'],
            'status'         => ['required', Rule::in(['draft', 'lobby', 'phase1', 'phase2', 'phase3', 'results', 'finished'])],
            'current_phase'  => ['required', 'integer', 'min:0', 'max:3'],

            'phase1_count'   => ['nullable', 'integer', 'min:0', 'max:100'],
            'phase2_count'   => ['nullable', 'integer', 'min:0', 'max:100'],
            'phase3_count'   => ['nullable', 'integer', 'min:0', 'max:100'],

            'settings_json'  => ['nullable', 'string'],
            'starts_at'      => ['nullable', 'date'],
            'ends_at'        => ['nullable', 'date', 'after_or_equal:starts_at'],
            'phase_ends_at'  => ['nullable', 'date'],
        ];
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
    }

    public function store(): GameSession
    {
        $this->validate();

        // Parsear settings_json si viene algo
        $settings = [];
        if (trim($this->settings_json) !== '') {
            $decoded = json_decode($this->settings_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Lanzar error de validación amigable
                $this->addError('settings_json', 'El JSON no es válido.');
                $this->validate(); // fuerza que se muestre
            }
            $settings = $decoded ?: [];
        }

        // Generar código único de 6 chars
        do {
            $code = Str::upper(Str::random(6));
        } while (GameSession::where('code', $code)->exists());

        // Convertir datetime-local (string) a formato Y-m-d H:i:s (opcional, Eloquent castea a datetime)
        $startsAt    = $this->starts_at ? date('Y-m-d H:i:s', strtotime($this->starts_at)) : null;
        $endsAt      = $this->ends_at ? date('Y-m-d H:i:s', strtotime($this->ends_at)) : null;
        $phaseEndsAt = $this->phase_ends_at ? date('Y-m-d H:i:s', strtotime($this->phase_ends_at)) : null;

        return GameSession::create([
            'code'           => $code,
            'title'          => $this->title,
            'status'         => $this->status,
            'current_phase'  => $this->current_phase,
            'phase1_count'   => $this->phase1_count,
            'phase2_count'   => $this->phase2_count,
            'phase3_count'   => $this->phase3_count,
            'settings_json'  => $settings,
            'starts_at'      => $startsAt,
            'ends_at'        => $endsAt,
            'phase_ends_at'  => $phaseEndsAt,
        ]);
    }
}
