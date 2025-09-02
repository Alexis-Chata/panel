<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Roles
        $admin    = Role::firstOrCreate(['name' => 'Admin']);
        $docente  = Role::firstOrCreate(['name' => 'Docente']);
        $estudiante = Role::firstOrCreate(['name' => 'Estudiante']);

        // Permisos base (ajusta a tu gusto)
        $perms = [
            'sessions.manage', // crear/editar partidas
            'sessions.run',    // avanzar preguntas, pausar, revelar
            'sessions.play',   // unirse y responder
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        $admin->givePermissionTo($perms);
        $docente->givePermissionTo(['sessions.manage', 'sessions.run', 'sessions.play']);
        $estudiante->givePermissionTo(['sessions.play']);
    }
}
