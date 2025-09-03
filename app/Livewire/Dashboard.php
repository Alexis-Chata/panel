<?php

namespace App\Livewire;

use App\Models\GameSession;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $active = GameSession::where('is_active', true)->latest()->first();

        return view('livewire.dashboard', compact('active'))
            ->layout('layouts.adminlte', [
                'title' => 'Panel',
                'header' => 'Panel de Inicio',
            ]);
    }
}
