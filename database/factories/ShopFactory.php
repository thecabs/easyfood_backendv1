<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class ShopFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
        'nom' => $this->faker->unique()->company(),
        'adresse' => $this->faker->address(),
        'ville' => $this->faker->city(),
        'quartier' => $this->faker->streetName(),
        'id_gestionnaire' => null, // injecté dynamiquement

        ];
    }
}
