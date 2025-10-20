<?php

namespace App\Livewire;

use App\Livewire\Forms\ConfiguracionForm;
use App\Models\Configuracion;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class AjusteSistema extends Component
{
    use WithFileUploads;
    public $logo,$favicon;
    public ConfiguracionForm $ajustesistemaform;

   public function mount()
    {
        // Buscar configuración con ID 1
        $configuracion = Configuracion::find(1);

        // Si no existe, la crea con valores por defecto
        if (!$configuracion)
        {
            $configuracion = Configuracion::create([
                'name' => 'Nombre del sistema', // puedes personalizarlo
                'logo' => null,
                'favicon' =>  null,
            ]);
        }

        $this->ajustesistemaform->reset();
        $this->ajustesistemaform->set($configuracion);
    }

    public function actualizar_ajustes(){
        $this->validate(
            [
                'logo' => 'nullable|image|max:2048',
                'favicon' => 'nullable|file|mimes:ico|max:512',
            ]);

        $mensaje = $this->ajustesistemaform->store_updated($this->logo,$this->favicon);
        $this->reset('logo','favicon');
        if ($mensaje['status'] === 'success') {$this->dispatch('success', $mensaje['message']);}
        else {$this->dispatch('error', $mensaje['message']);}
    }

    public function render()
    {
        return view('livewire.ajuste-sistema');
    }
}
