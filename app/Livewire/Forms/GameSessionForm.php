<?php

namespace App\Livewire\Forms;

use App\Models\GameSession;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Form;
use Carbon\Carbon;

class GameSessionForm extends Form
{
    public ?GameSession $gameSession = null;

    // === Campos del formulario ===
    public ?string $code = null;                     // size:12 | alpha_num | unique
    public ?string $title = null;                    // nullable
    public string  $phase_mode = 'basic';            // enum: basic
    public int     $questions_total = 10;            // uint8
    public int     $timer_default = 30;              // uint16
    public string  $student_view_mode = 'completo';      // enum: choices_only | full
    public bool    $is_active = false;
    public bool    $is_running = false;
    public int     $current_q_index = 0;             // uint16
    public ?string $current_q_started_at = null;     // datetime string (Y-m-d H:i:s) o null
    public bool    $is_paused = false;
    public ?string $starts_at = null;                // datetime string (Y-m-d H:i:s) o null

    // ===== Helpers =====
    protected function rules(): array
    {
        return [
            'code' => [
                'nullable', 'string', 'size:12', 'alpha_num',
                Rule::unique('game_sessions', 'code')->ignore($this->gameSession?->id),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'phase_mode' => ['required', Rule::in(['basic'])],
            'questions_total' => ['required', 'integer', 'min:1', 'max:255'],
            'timer_default' => ['required', 'integer', 'min:5', 'max:3600'],
            'student_view_mode' => ['required', Rule::in(['solo_alternativas','completo'])],
            'is_active' => ['boolean'],
            'is_running' => ['boolean'],
            'current_q_index' => ['required', 'integer', 'min:0', 'max:65535'],
            'current_q_started_at' => ['nullable', 'date'],
            'is_paused' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
        ];
    }

    protected function defaults(): void
    {
        $this->phase_mode = 'basic';
        $this->questions_total = 10;
        $this->timer_default = 30;
        $this->student_view_mode = 'full';
        $this->is_active = false;
        $this->is_running = false;
        $this->current_q_index = 0;
        $this->current_q_started_at = null;
        $this->is_paused = false;
        $this->starts_at = null;
    }

    public function set(GameSession $gameSession): void
    {
        $this->gameSession = $gameSession;

        $this->code = $gameSession->code;
        $this->title = $gameSession->title;
        $this->phase_mode = $gameSession->phase_mode ?? 'basic';
        $this->questions_total = (int) $gameSession->questions_total;
        $this->timer_default = (int) $gameSession->timer_default;
        $this->student_view_mode = $gameSession->student_view_mode ?? 'full';
        $this->is_active = (bool) $gameSession->is_active;
        $this->is_running = (bool) $gameSession->is_running;
        $this->current_q_index = (int) $gameSession->current_q_index;
        $this->current_q_started_at = optional($gameSession->current_q_started_at)->format('Y-m-d H:i:s');
        $this->is_paused = (bool) $gameSession->is_paused;
        $this->starts_at = optional($gameSession->starts_at)->format('Y-m-d H:i:s');
    }

    public function store(): array
    {
        // Genera code si no viene (12 chars alfanum. mayúsculas)
        if (blank($this->code)) {
            $this->code = $this->generateUniqueCode(12);
        }

        $data = $this->validatePayload();

        $this->gameSession = GameSession::create($data);

        return ['status' => 'success', 'message' => 'Sesión de juego creada correctamente.'];
    }

    public function update(): array
    {
        if (!$this->gameSession?->id) {
            return ['status' => 'error', 'message' => 'No se ha seleccionado ninguna sesión'];
        }

        $data = $this->validatePayload();

        $this->gameSession->update($data);

        return ['status' => 'success', 'message' => 'Sesión de juego actualizada correctamente.'];
    }

    public function store_updated(): array
    {
        return $this->gameSession?->id ? $this->update() : $this->store();
    }

    public function eliminar(): array
    {
        if (!$this->gameSession) {
            return ['status' => 'error', 'message' => 'No se ha seleccionado ninguna sesión'];
        }

        if ($this->gameSession->is_running) {
            return ['status' => 'error', 'message' => 'No puedes eliminar una sesión en ejecución. Pausa o detén primero.'];
        }

        $this->gameSession->delete();
        $this->reset(); // limpia el form

        return ['status' => 'success', 'message' => 'Sesión eliminada correctamente'];
    }

    // ===== Internos =====
    protected function validatePayload(): array
    {
        $this->validate();

        // Normaliza fechas (permitir strings o null)
        $currentStartedAt = $this->current_q_started_at
            ? Carbon::parse($this->current_q_started_at)
            : null;

        $startsAt = $this->starts_at
            ? Carbon::parse($this->starts_at)
            : null;

        return [
            'code' => $this->code, // ya validado unique/size/alpha_num
            'title' => $this->title,
            'phase_mode' => $this->phase_mode,
            'questions_total' => $this->questions_total,
            'timer_default' => $this->timer_default,
            'student_view_mode' => $this->student_view_mode,
            'is_active' => (bool) $this->is_active,
            'is_running' => (bool) $this->is_running,
            'current_q_index' => $this->current_q_index,
            'current_q_started_at' => $currentStartedAt,
            'is_paused' => (bool) $this->is_paused,
            'starts_at' => $startsAt,
        ];
    }

    protected function generateUniqueCode(int $length = 12): string
    {
        do {
            // Solo alfanumérico mayúscula, evita confusiones (0/O, 1/I opcional)
            $candidate = Str::upper(Str::random($length));
        } while (GameSession::where('code', $candidate)->exists());

        return $candidate;
    }
}
