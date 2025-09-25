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

        $student = User::firstOrCreate(
            ['email' => '71878601@gmail.com'],
            ['name' => 'Frank Supo', 'password' => Hash::make('71878601')]
        );

        $student = User::firstOrCreate(
            ['email' => '75653249@gmail.com'],
            ['name' => 'Giovany Alejo', 'password' => Hash::make('75653249')]
        );

        $student = User::firstOrCreate(
            ['email' => '72186496@gmail.com'],
            ['name' => 'Sofia Huanaco', 'password' => Hash::make('72186496')]
        );

        $student = User::firstOrCreate(
            ['email' => '60840799@gmail.com'],
            ['name' => 'Engie Soto', 'password' => Hash::make('60840799')]
        );

        $student = User::firstOrCreate(
            ['email' => '61244254@gmail.com'],
            ['name' => 'Marlith Hilari', 'password' => Hash::make('61244254')]
        );

        $student = User::firstOrCreate(
            ['email' => 'anonimo@gmail.com'],
            ['name' => 'Milagros Calcina', 'password' => Hash::make('milagros')]
        );

        $student = User::firstOrCreate(
            ['email' => 'armando@gmail.com'],
            ['name' => 'Armando', 'password' => Hash::make('armando')]
        );

        $student = User::firstOrCreate(
            ['email' => 'carlos@gmail.com'],
            ['name' => 'Carlos', 'password' => Hash::make('carlos')]
        );

        $student = User::firstOrCreate(
            ['email' => 'israel@gmail.com'],
            ['name' => 'israel', 'password' => Hash::make('israel')]
        );

        $student = User::firstOrCreate(
            ['email' => 'mirian@gmail.com'],
            ['name' => 'mirian', 'password' => Hash::make('mirian')]
        );

        $student = User::firstOrCreate(
            ['email' => 'ebelyn@gmail.com'],
            ['name' => 'ebelyn', 'password' => Hash::make('ebelyn')]
        );

    }
}
