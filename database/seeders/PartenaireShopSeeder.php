<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Str;
use App\Models\PartenaireShop;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PartenaireShopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function runWithParams(int $nbrShop): void
    {
         // 4. Générer 50 shops indépendants
         for ($k = 0; $k <= $nbrShop; $k++) {
            $ville = fake()->city();
            $quartier = fake()->streetName();
            $nom = 'Shop ' . strtoupper(Str::random(5)) . " $ville $quartier";

            $shop = PartenaireShop::factory()->create([
                'nom' => $nom,
                'adresse' => fake()->address(),
                'ville' => $ville,
                'quartier' => $quartier,
            ]);
            $gestShop = User::factory()->create([
                'role' => 'shop_gest',
                'ville' => $ville,
                'quartier' => $quartier,
                'id_shop'=>$shop->id_shop,
            ]);

            // Générer un nom unique par combinaison (nom, ville, quartier)

            $shop->update(['id_gestionnaire' => $gestShop->id_user]);
        }

    }
}
