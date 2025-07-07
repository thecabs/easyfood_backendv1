<?php

namespace Database\Factories;

use App\Models\Categorie;
use App\Models\PartenaireShop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Produit>
 */
class ProduitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom' => $this->faker->words(2, true),
            'prix_ifc' => $this->faker->randomFloat(2, 1000, 10000),
            'prix_shop' => $this->faker->randomFloat(2, 1000, 10000),
            'statut' => 'actif',
            'code_barre' => $this->faker->unique()->ean13,
        ];
    }
}
