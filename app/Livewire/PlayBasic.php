<?php

namespace App\Livewire;

use App\Events\AnswerSubmitted;
use App\Events\GameSessionStateChanged;
use App\Models\Answer;
use App\Models\GameSession;
use App\Models\SessionParticipant;
use App\Models\SessionQuestion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; // ▼ NUEVO
use Livewire\Attributes\On;
use Livewire\Component;

class PlayBasic extends Component
{
    public GameSession $gameSession;
    public ?SessionParticipant $me = null;
    public ?SessionQuestion $current = null;
    public ?int $answered_option_id = null;
    public ?int $last_seen_index = null;

    /** @var array<int,array{id:int,name:string,first_name:string,photo_url:string}> */
    public array $roster = []; // ▼ NUEVO

    public function mount(GameSession $gameSession)
    {
        $this->gameSession = $gameSession->fresh();
        $this->me = SessionParticipant::where('game_session_id', $this->gameSession->id)
            ->where('user_id', Auth::id())->first();

        abort_unless($this->me, 403, 'No estás unido a esta partida.');

        $this->syncCurrent();
        $this->refreshRoster(); // ▼ NUEVO
    }

    public function syncCurrent(): void
    {
        $idx = $this->gameSession->current_q_index;
        $this->current = $this->gameSession->sessionQuestions()
            ->with('question.options')
            ->where('q_order', $idx)->first();

        $this->last_seen_index = $idx;

        if ($this->current) {
            $ans = Answer::where('session_participant_id', $this->me->id)
                ->where('session_question_id', $this->current->id)->first();

            // Para preguntas de alternativa
            $this->answered_option_id = $ans?->question_option_id;
            $this->answered_any       = (bool) $ans; // 👈 clave para short

            // Para preguntas cortas: mostrar el texto si ya respondió
            if ($this->current->question->qtype === 'short') {
                $this->respuesta = $ans?->text ?? '';
            } else {
                $this->respuesta = '';
            }
        } else {
            $this->answered_option_id = null;
            $this->answered_any       = false;
            $this->respuesta          = '';
        }
    }

    // ▼ NUEVO: construir lista de avatares (con Jetstream o Storage, con fallback)
    private function buildRoster(): array
    {
        return SessionParticipant::with(['user:id,name,email,profile_photo_path'])
            ->where('game_session_id', $this->gameSession->id)
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($p) {
                $u = $p->user;
                // Si usas Jetstream con HasProfilePhoto:
                $url = method_exists($u, 'getProfilePhotoUrlAttribute')
                    ? ($u->profile_photo_url ?? null)
                    : null;

                // Si no, intenta con Storage público (storage:link)
                if (!$url && $u->profile_photo_path) {
                    $url = Storage::disk('public')->exists($u->profile_photo_path)
                        ? Storage::url($u->profile_photo_path)
                        : null;
                }

                // Fallback final (ui-avatars por nombre)
                if (!$url) {
                    $url = 'https://ui-avatars.com/api/?name=' . urlencode($u->name) . '&background=0D8ABC&color=fff&size=128';
                }

                $firstName = explode(' ', trim($u->name ?? ''))[0] ?? '';

                return [
                    'id'         => (int) $p->id,
                    'name'       => (string) $u->name,
                    'first_name' => (string) $firstName,
                    'photo_url'  => (string) $url,
                ];
            })
            ->values()
            ->toArray();
    }

    #[On('refreshRoster')] // opcional: por si disparas este evento desde Echo
    public function refreshRoster(): void // ▼ NUEVO
    {
        $this->roster = $this->buildRoster();
    }

    public function answer(?int $optionId, $ignoreElapsedFromClient = null)
    {
        if (!$this->gameSession->is_running || $this->gameSession->is_paused) return;

        $exists = Answer::where('session_participant_id', $this->me->id)
            ->where('session_question_id', $this->current->id)->exists();
        if ($exists) return;

        $timerSec = (int)($this->current->timer_override ?? $this->gameSession->timer_default);
        $startAt  = $this->gameSession->current_q_started_at;
        $elapsedSec = $startAt ? max(0, $startAt->diffInRealSeconds(now(), false)) : 0;
        $serverElapsedMs = (int) min($timerSec * 1000, $elapsedSec * 1000);

        $option = $optionId ? $this->current->question->options->firstWhere('id', $optionId) : null;
        $isCorrect = $option ? (bool)$option->is_correct : false;

        Answer::create([
            'session_participant_id' => $this->me->id,
            'session_question_id'    => $this->current->id,
            'question_option_id'     => $optionId,
            'is_correct'             => $isCorrect,
            'time_ms'                => $serverElapsedMs,
            'answered_at'            => now(),
        ]);

        $sum = Answer::where('session_participant_id', $this->me->id);
        $this->me->update([
            'score'         => (clone $sum)->where('is_correct', true)->count(),
            'time_total_ms' => (clone $sum)->sum('time_ms'),
        ]);

        $this->answered_option_id = $optionId;
        $this->answered_any = true;

        $pCount = SessionParticipant::where('game_session_id', $this->gameSession->id)
            ->where('is_ignored', false)
            ->count();
        $aCount = Answer::where('session_question_id', $this->current->id)
            ->whereIn('session_participant_id', SessionParticipant::where('game_session_id', $this->gameSession->id)
                ->where('is_ignored', false)
                ->pluck('id'))
            ->count();

        AnswerSubmitted::dispatch($this->gameSession->id, $aCount, $pCount);

        if ($aCount >= $pCount && $pCount > 0) {
            $this->gameSession->update(['is_paused' => true]);
            GameSessionStateChanged::dispatch($this->gameSession->id, [
                'is_running' => true,
                'is_paused' => true,
                'current_q_index' => $this->gameSession->current_q_index,
                'current_q_started_at' => optional($this->gameSession->current_q_started_at)?->toIso8601String(),
            ]);
        }
    }

    #[On('syncState')]
    public function syncState($payload = null)
    {
        $this->gameSession->refresh();
        $this->syncCurrent();
        $this->refreshRoster(); // ▼ NUEVO: por si cambió algo en lobby
    }

    #[On('refreshStats')]
    public function refreshStats($payload = null)
    {
        $this->gameSession->refresh();
    }

    // Propiedad
    public string $respuesta = '';
    public bool $answered_any = false;

    // Enviar
    // D) enviarRespuestaCorta(): marca respondido para short
    public function enviarRespuestaCorta(): void
    {
        if (!$this->gameSession->is_running || $this->gameSession->is_paused) {
            return;
        }

        if (!$this->current || $this->current->question->qtype !== 'short') {
            return;
        }

        $this->validate([
            'respuesta' => 'required|string|max:255', // ajusta el max si quieres permitir más texto
        ]);

        // Evitar doble respuesta
        $exists = Answer::where('session_participant_id', $this->me->id)
            ->where('session_question_id', $this->current->id)->exists();

        if ($exists) {
            return;
        }

        // Calcular tiempo igual que en answer()
        $timerSec = (int) ($this->current->timer_override ?? $this->gameSession->timer_default);
        $startAt  = $this->gameSession->current_q_started_at;
        $elapsedSec = $startAt ? max(0, $startAt->diffInRealSeconds(now(), false)) : 0;
        $serverElapsedMs = (int) min($timerSec * 1000, $elapsedSec * 1000);

        // Guardar SOLO texto, sin autocorrección ni score automático
        Answer::create([
            'session_participant_id' => $this->me->id,
            'session_question_id'    => $this->current->id,
            'question_option_id'     => null,
            'is_correct'             => false,          // se cambiará luego al revisar
            'time_ms'                => $serverElapsedMs,
            'answered_at'            => now(),
            'text'                   => $this->respuesta,
            'matched_id'             => null,
            'score'                  => 0,              // se pondrá luego al revisar
        ]);

        // Actualizar totales del participante (misma lógica que answer())
        $sum = Answer::where('session_participant_id', $this->me->id);
        $this->me->update([
            'score'         => (clone $sum)->where('is_correct', true)->count(),
            'time_total_ms' => (clone $sum)->sum('time_ms'),
        ]);

        // Marcar como respondido para bloquear auto-respuesta del timer
        $this->answered_any       = true;
        $this->answered_option_id = null; // en short no aplica opción
        $this->dispatch('respuesta_guardada', correcto: false, puntaje: 0);
        $this->respuesta = '';

        // Contar respuestas para esta pregunta
        $pQuery = SessionParticipant::where('game_session_id', $this->gameSession->id)
            ->where('is_ignored', false);

        $pCount = (clone $pQuery)->count();

        $aCount = Answer::where('session_question_id', $this->current->id)
            ->whereIn('session_participant_id', (clone $pQuery)->pluck('id'))
            ->count();

        // Notificar al host que alguien más respondió (si usas este evento en el panel del docente)
        AnswerSubmitted::dispatch($this->gameSession->id, $aCount, $pCount);

        // Pausar sesión si todos respondieron (mismo comportamiento que en answer())
        if ($aCount >= $pCount && $pCount > 0) {
            $this->gameSession->update(['is_paused' => true]);
            GameSessionStateChanged::dispatch($this->gameSession->id, [
                'is_running'            => true,
                'is_paused'             => true,
                'current_q_index'       => $this->gameSession->current_q_index,
                'current_q_started_at'  => optional($this->gameSession->current_q_started_at)?->toIso8601String(),
            ]);
        }
    }

    public function render()
    {
        $this->gameSession->refresh();

        if ($this->last_seen_index !== $this->gameSession->current_q_index) {
            $this->syncCurrent();
        }

        $secondsLeft = 0;
        if ($this->current && $this->gameSession->is_running && !$this->gameSession->is_paused) {
            $timerSec = (int) ($this->current->timer_override ?? $this->gameSession->timer_default);
            $startAt  = $this->gameSession->current_q_started_at;
            if ($startAt) {
                $elapsed = max(0, $startAt->diffInRealSeconds(now(), false));
                $secondsLeft = max(0, $timerSec - $elapsed);
            } else {
                $secondsLeft = $timerSec;
            }
        }

        return view('livewire.play-basic', [
            'secondsLeft' => $secondsLeft,
        ])->layout('layouts.adminlte', [
            'title'  => 'Jugar',
            'header' => ($this->gameSession->title ?? 'Partida') . ' [' . $this->gameSession->code . ']',
        ]);
    }
}
