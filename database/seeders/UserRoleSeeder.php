<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => Hash::make('password')]
        );
        $admin->syncRoles(['Admin']);

        // Docente
        $teacher = User::firstOrCreate(
            ['email' => 'docente@example.com'],
            ['name' => 'Docente', 'password' => Hash::make('password')]
        );
        $teacher->syncRoles(['Docente']);

        // Estudiante
        $student = User::firstOrCreate(
            ['email' => 'estudiante@example.com'],
            ['name' => 'Estudiante', 'password' => Hash::make('password')]
        );
        $student->syncRoles(['Estudiante']);
    }
}
