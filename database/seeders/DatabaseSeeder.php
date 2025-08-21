<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permission = Permission::create(['name' => 'manage-sessions']);

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $user->givePermissionTo(['manage-sessions']);

        User::factory(10)->create();

        $this->call([
            QuestionBankSeeder::class,
            GameSessionSeeder::class,
        ]);
    }
}
