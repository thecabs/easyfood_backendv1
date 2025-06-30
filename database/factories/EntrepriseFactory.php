<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Entreprise>
 */
class EntrepriseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom' => $this->faker->company(),
            'secteur_activite' => $this->faker->word(),
            'ville' => $this->faker->city(),
            'quartier' => $this->faker->streetName(),
            'adresse' => $this->faker->address(),
            'id_assurance' => null,
        ];
    }
}
