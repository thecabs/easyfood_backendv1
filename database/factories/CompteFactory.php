<?php

namespace Database\Factories;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Compte>
 */
class CompteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numero_compte' => 'TEMP', // sera remplacé après création de l'employé
            'solde' => 0,
            'date_creation' => now(),
            'pin' => Hash::make('0000'), // ou ton $defaultPin
            // 'id_user' sera injecté dynamiquement
    
        ];
    }
}
