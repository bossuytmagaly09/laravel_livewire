<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder; // basis seeder

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SupportAgentsSeeder::class, // //NIEUW: seed supportmedewerkers voor assignment
        ]);
    }
}
