<?php

namespace App\Livewire;

use App\Livewire\Forms\GameSessionForm;
use App\Models\GameSession;
use App\Models\Question;
use App\Models\SessionQuestion;
use Livewire\Component;

class ManageSessions extends Component
{
    public GameSessionForm $form;

    public function toggleStudentViewMode(GameSession $gameSession): void
    {
        $gameSession->student_view_mode = $gameSession->student_view_mode === 'completo'
            ? 'solo_alternativas'
            : 'completo';
        $gameSession->save();

    }

    public function getNextViewModeLabel(GameSession $gameSession): string
    {
        // Solo para construir el mensaje de confirmación en Blade (opcional)
        return $gameSession->student_view_mode === 'full'
            ? 'Solo alternativas'
            : 'Completo';
    }


    public function nuevo()
    {
        // Defaults del form (ya están definidos en el form)
        $this->form->reset();
    }

    public function save_session()
    {
        // Validación (incluye enum y rangos)
        // Primero verifica banco de preguntas:
        $totalAvailable = Question::count();
        if ($totalAvailable === 0) {
            $this->addError('form.title', 'No hay preguntas en el banco. Importa o crea algunas primero.');
            return;
        }

        // Ajusta el total solicitado al disponible si es necesario (y al límite del schema)
        $this->form->questions_total = min($this->form->questions_total ?? 10, $totalAvailable);

        // Crea la sesión desde el Form (autogenera code si está vacío)
        $res = $this->form->store();

        /** @var GameSession $session */
        $session = $this->form->gameSession;

        // Persistimos preguntas aleatorias para la sesión
        $qs = Question::inRandomOrder()->take($this->form->questions_total)->get();
        $payload = [];
        foreach ($qs as $i => $q) {
            $payload[] = [
                'game_session_id' => $session->id,
                'question_id'     => $q->id,
                'q_order'         => $i,
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }
        if (!empty($payload)) {
            SessionQuestion::insert($payload);
        }

        // Asegura que el contador guardado coincida con lo seleccionado
        if ($session->questions_total != $this->form->questions_total) {
            $session->update(['questions_total' => $this->form->questions_total]);
        }

        // Limpia el form y cierra modal (Bootstrap client-side)
        $this->form->reset();
        $this->dispatch('cerrar_modal_gamesession');

        session()->flash('ok', "Partida creada: {$session->code}");
    }

    public function toggleActive(GameSession $gameSession)
    {
          $gameSession->update(['is_active' => !$gameSession->is_active]);
    }

    public function endSession(GameSession $gameSession)
    {
        $gameSession->update([
            'is_active'  => false,
            'is_running' => false,
            'is_paused'  => false,
        ]);
    }

    public function run(GameSession $gameSession)
    {
        return redirect()->route('sessions.run', $gameSession);
    }

    public function render()
    {
        $sessions = GameSession::latest()->paginate(10);

        return view('livewire.manage-sessions', compact('sessions'))
            ->layout('layouts.adminlte', [
                'title'  => 'Partidas',
                'header' => 'Gestionar Partidas',
            ]);
    }
}
