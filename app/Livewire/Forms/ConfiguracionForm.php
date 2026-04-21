<?php

namespace App\Livewire\Forms;

use App\Models\Configuracion;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\File;
use Livewire\Form;

class ConfiguracionForm extends Form
{
    public ?Configuracion $configuracion;

    #[Rule('nullable|image|max:2048')] # Validación básica para imagen
    public $logo;

    #[Rule('nullable|file|mimes:ico|max:512')] # Validación básica para imagen
    public $favicon;

    #[Rule('nullable|string|max:255')]
    public $name;

    public function set(Configuracion $configuracion)
    {
        $this->configuracion = $configuracion;
        $this->name = $configuracion->name;
        $this->logo = null; // no precargamos archivo
    }

    public function store($imagen = null,$favicon = null)
    {
        $this->validate();

        $this->configuracion = Configuracion::create([
            'name' => $this->name,
        ]);

        if ($imagen) {$this->subir_logo($imagen);}
        if ($favicon) {$this->subir_favicon($favicon);}

        return ['status' => 'success', 'message' => 'Configuración creada correctamente.'];
    }

    public function update($imagen = null,$favicon = null)
    {
        $this->validate();

        $this->configuracion->update([
            'name' => $this->name,
        ]);

        if ($imagen) {$this->subir_logo($imagen);}
        if ($favicon) {$this->subir_favicon($favicon);}
        return ['status' => 'success', 'message' => 'Configuración actualizada correctamente.'];
    }

    public function store_updated($imagen = null,$favicon = null)
    {
        return $this->configuracion?->id ? $this->update($imagen,$favicon) : $this->store($imagen,$favicon);
    }

    public function eliminar()
    {
        if (!$this->configuracion) {
            return ['status' => 'error', 'message' => 'No se ha seleccionado ninguna configuración'];
        }

        $this->eliminar_logo();

        $this->configuracion->delete();

        return ['status' => 'success', 'message' => 'Configuración eliminada correctamente'];
    }

    public function subir_logo($imagen)
    {
        $this->eliminar_logo();
        $extension = $imagen->extension();
        $path = $imagen->storeAs('configuraciones', $this->configuracion->id . "-" . time() . "." . $extension,'public');
        $this->configuracion->logo = 'storage/' . $path;
        $this->configuracion->save();
    }

    public function eliminar_logo()
    {
        if ($this->configuracion->logo) {
            $ruta = str_replace('storage/', '', $this->configuracion->logo);
            Storage::delete($ruta);
        }
    }


     public function subir_favicon($imagen)
    {
        $this->eliminar_favicon();

        $extension = $imagen->extension();
        $path = $imagen->storeAs('configuraciones', $this->configuracion->id . "-ico-" . time() . "." . $extension,'public');

        $this->configuracion->favicon = 'storage/' . $path;
        $this->configuracion->save();

        // === NUEVO: copiar al path de AdminLTE ===
        // Asegura la carpeta /public/favicons
        File::ensureDirectoryExists(public_path('favicons'));

        // Resuelve la ruta origen (funciona si guardaste en disco 'public' o en el default)
        $sourcePublic = storage_path('app/public/' . $path);
        $sourceLocal  = storage_path('app/' . $path);
        $source       = file_exists($sourcePublic) ? $sourcePublic : $sourceLocal;

        // Copia como favicon por defecto para AdminLTE
        File::copy($source, public_path('favicons/favicon.ico'));
        // ==========================================
    }


    public function eliminar_favicon()
    {
        if ($this->configuracion->favicon) {
            $ruta = str_replace('storage/', '', $this->configuracion->favicon);
            Storage::delete($ruta);
        }
    }
}
