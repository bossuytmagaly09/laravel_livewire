<?php

namespace Database\Seeders;

use App\Models\User;

// nodig om users aan te maken
use Illuminate\Database\Seeder;

// basis seeder
use Illuminate\Support\Facades\Hash;

// nodig om wachtwoorden te hashen

class SupportAgentsSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'sarah@supportdesk.test'], // //NIEUW: unieke sleutel om dubbels te vermijden
            [
                'name' => 'Sarah Support', // //NIEUW: eerste supportmedewerker
                'password' => Hash::make('password'), // //NIEUW: testwachtwoord
            ]
        );
        User::updateOrCreate(
            ['email' => 'tom@supportdesk.test'], // //NIEUW: tweede supportmedewerker
            [
                'name' => 'Tom Triage', // //NIEUW: tweede supportmedewerker
                'password' => Hash::make('password'), // //NIEUW: testwachtwoord
            ]
        );

        User::updateOrCreate(
            ['email' => 'els@supportdesk.test'], // //NIEUW: derde supportmedewerker
            [
                'name' => 'Els Escalation', // //NIEUW: derde supportmedewerker
                'password' => Hash::make('password'), // //NIEUW: testwachtwoord
            ]
        );
    }
}
