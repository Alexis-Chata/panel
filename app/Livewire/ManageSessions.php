<?php

namespace App\Livewire;

use App\Livewire\Forms\GameSessionForm;
use App\Models\Archivo;
use App\Models\GameSession;
use App\Models\Question;
use App\Models\SessionQuestion;
use Livewire\Component;
use Livewire\WithFileUploads;

class ManageSessions extends Component
{
    public GameSessionForm $form;
    public array $uploads = [];
    use WithFileUploads;

    public function toggleStudentViewMode(GameSession $gameSession): void
    {
        $gameSession->student_view_mode = $gameSession->student_view_mode === 'completo'
            ? 'solo_alternativas'
            : 'completo';
        $gameSession->save();
    }

    public function getNextViewModeLabel(GameSession $gameSession): string
    {
        // (opcional) si lo usas en Blade
        return $gameSession->student_view_mode === 'completo'
            ? 'Solo alternativas'
            : 'Completo';
    }

    public function removeArchivo(Archivo $archivo): void
    {
        // delega en el Form, que ya valida pertenencia
        $res = $this->form->deleteArchivo($archivo);
        $this->dispatch('toast', body: $res['message'] ?? 'Actualizado');

        // Mantén el modal abierto y recarga archivos reflejados
        $this->form->gameSession?->refresh();
    }

    public function nuevo(): void
    {
        $this->form->reset();
        $this->uploads = [];
    }

    public function editar(GameSession $gameSession): void
    {
        $this->form->set($gameSession);
        $this->uploads = [];
        // el modal se abre desde el botón (Bootstrap), no desde backend
    }

    public function save_session()
    {
        // Validación de archivos
        $this->validate([
            'uploads.*' => 'file|max:5120|mimes:pdf,jpg,jpeg,png,webp,doc,docx,ppt,pptx,zip',
        ]);

        $isEdit = (bool) $this->form->gameSession?->id;

        if (! $isEdit) {
            // Crear
            $totalAvailable = Question::count();
            if ($totalAvailable === 0) {
                $this->addError('form.title', 'No hay preguntas en el banco. Importa o crea algunas primero.');
                return;
            }

            $this->form->questions_total = min($this->form->questions_total ?? 10, $totalAvailable);
            $this->form->store();
            /** @var GameSession $session */
            $session = $this->form->gameSession;

            // Adjuntos (si hay)
            if (!empty($this->uploads)) {
                $this->form->attachFiles($this->uploads);
            }

            // Seleccionar preguntas aleatorias
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
            if ($payload) {
                SessionQuestion::insert($payload);
            }

            if ($session->questions_total != $this->form->questions_total) {
                $session->update(['questions_total' => $this->form->questions_total]);
            }

            $msg = "Partida creada: {$session->code}";
        } else {
            // Editar
            // (Opcional) puedes limitar questions_total al disponible, pero no tocamos SessionQuestions aquí
            $this->form->update();

            if (!empty($this->uploads)) {
                $this->form->attachFiles($this->uploads); // añade nuevos adjuntos
            }

            $msg = 'Partida actualizada correctamente.';
        }

        // Reset & cerrar modal
        $this->uploads = [];
        $this->form->reset();
        $this->dispatch('cerrar_modal_gamesession');

        session()->flash('ok', $msg);
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
