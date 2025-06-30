<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Assurance;
use App\Models\Entreprise;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EmployeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 100; $i++) {
            // Crée le gestionnaire de l'assurance
            $gestAssurance = User::factory()->create([
                'role' => 'assurance_gest',
                'statut' => 'actif',
            ]);

            // Crée l'assurance avec id_gestionnaire
            $assurance = Assurance::factory()->create([
                'id_gestionnaire' => $gestAssurance->id_user,
            ]);

            for ($j = 1; $j <= 10; $j++) {

                // Crée le gestionnaire de l'entreprise
                $gestEntreprise = User::factory()->create([
                    'role' => 'entreprise_gest',
                    'statut' => 'actif',
                ]);

                // Crée l'entreprise avec id_gestionnaire
                $entreprise = Entreprise::factory()->create([
                    'id_assurance' => $assurance->id_assurance,
                    'id_gestionnaire' => $gestEntreprise->id_user,
                ]);

                // Crée 5 employés liés à l'entreprise
                User::factory()->count(5)->create([
                    'role' => 'employe',
                    'id_entreprise' => $entreprise->id_entreprise,
                    'statut' => 'actif',
                ]);
            }
        }

    }
}
