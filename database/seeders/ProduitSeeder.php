<?php

namespace Database\Seeders;

use App\Models\Stock;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProduitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for($i = 0; $i < 20; $i++) {
            $categorie = \App\Models\Categorie::factory()->create([
                'id_shop' => 134,
            ]);
            for($j = 0; $j < 30; $j++) {
                $produit = \App\Models\Produit::factory()->create([
                    'id_shop' => 134,
                    'id_categorie' => $categorie->id,
                ]);

                $stock = Stock::create([
                    'id_produit' => $produit->id_produit,
                    'quantite' => 0, // Quantité initiale nulle
                    'id_shop' => 134,
                ]);
            }
        }
    }
}
