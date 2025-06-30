<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use App\Models\Assurance;
use App\Models\Entreprise;
use Illuminate\Support\Str;
use App\Models\PartenaireShop;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
          // 1. Générer 100 assurances
          for ($i = 1; $i <= 100; $i++) {
            $codeIFC = 'IFC-' . str_pad($i, 5, '0', STR_PAD_LEFT); // ex: IFC-00001

            $assurance = Assurance::factory()->create(
              [  'code_ifc' => $codeIFC,]

            );

            $gestAssurance = User::factory()->create([
                'role' => 'assurance_gest',
                'id_assurance' => $assurance->id_assurance,
            ]);

            $assurance->update(['id_gestionnaire' => $gestAssurance->id_user]);

            // 2. Générer 10 entreprises par assurance
            for ($j = 1; $j <= 10; $j++) {
                $ville = fake()->city();
                $quartier = fake()->streetName();

                $gestEntreprise = User::factory()->create([
                    'role' => 'entreprise_gest',
                    'ville' => $ville,
                    'quartier' => $quartier,
                ]);

                $entreprise = Entreprise::factory()->create([
                    'id_assurance' => $assurance->id_assurance,
                    'id_gestionnaire' => $gestEntreprise->id_user,
                    'ville' => $ville,
                    'quartier' => $quartier,
                ]);

                $gestEntreprise->update(['id_entreprise' => $entreprise->id]);
                for ($l = 1; $l <= 10; $l++) {

                    // 3. Générer 5 employés par entreprise
                    User::factory()->create([
                        'role' => 'employe',
                        'id_entreprise' => $entreprise->id_entreprise,
                        'id_assurance' => $assurance->id_assurance,
                        'ville' => $ville,
                        'quartier' => $quartier,
                    ]);
                }
            }
        }

        // 4. Générer 50 shops indépendants
        for ($k = 1; $k <= 1; $k++) {
            $ville = fake()->city();
            $quartier = fake()->streetName();

            $gestShop = User::factory()->create([
                'role' => 'shop_gest',
                'ville' => $ville,
                'quartier' => $quartier,
            ]);

            // Générer un nom unique par combinaison (nom, ville, quartier)
            $nom = 'Shop ' . strtoupper(Str::random(5)) . " $ville $quartier";

            $shop = PartenaireShop::factory()->create([
                'nom' => $nom,
                'adresse' => fake()->address(),
                'ville' => $ville,
                'quartier' => $quartier,
                'id_gestionnaire' => $gestShop->id_user,
            ]);
            $gestShop->update(['id_shop' => $shop->id_shop]);
        }
    

    }
}
