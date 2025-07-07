<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $password = '12345678';
        return [
            'nom' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'tel' => $this->faker->phoneNumber(),
            'ville' => fake()->city(),
            'quartier' => fake()->streetName(),
            'password' => Hash::make($password),
            'statut' => 'actif',
        ];
    }

    public function configure(): static
{
    return $this->afterCreating(function (\App\Models\User $user) {
        \App\Models\Compte::create([
            'id_user' => $user->id_user,
            'numero_compte' => \App\Models\Compte::generateNumeroCompte($user),
            'solde' => 0,
            'date_creation' => now(),
            'pin' => Hash::make(1234),
        ]);
    });
}


    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
