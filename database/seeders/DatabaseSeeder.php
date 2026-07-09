<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use MeuCandidato\Party\Database\Seeders\PartySeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@meucandidato.com.br',
            'password' => bcrypt('password'),
        ]);

        $this->call([
            PartySeeder::class,
            BadgeDefinitionSeeder::class,
        ]);
    }
}
