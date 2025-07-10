<?php

namespace Database\Factories;

use App\Models\Roles_demande;
use App\Models\Statuts_demande;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Demande>
 */
class DemandeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    // admin - shop
    public function definition(): array
    {
        /**
         * Define the model's default state.
         *
         * @return array<string, mixed>
         */



        return [
            // ID auto-incrémenté ou généré par Laravel (inutile ici)
            'id_emetteur'      => -1,
            'id_destinataire'  => -1,
            'montant'          => $this->faker->randomFloat(2, 1000, 100000),
            'statut'           => $this->faker->randomElement([Statuts_demande::En_attente, Statuts_demande::Accorde, Statuts_demande::Refuse]),
            'motif'            => $this->faker->sentence,
            'created_at'             => null,
            'updated_at'             => null,
            'role'             => null,
        ];
    }
    // admin - entreprise
    // public function definition(): array
    // {
    //     /**
    //      * Define the model's default state.
    //      *
    //      * @return array<string, mixed>
    //      */

    //     $id_emetteurs = [1, 9887];
    //     $id_destinataires = [9887, 9896];
    //     $id_emetteur = $this->faker->randomElement([3, 4]);
    //      // Crée une date de création aléatoire entre 6 mois et aujourd’hui
    //      $createdAt = $this->faker->dateTimeBetween('-8 months', 'now');

    //      // updated_at toujours >= created_at
    //      $updatedAt = $this->faker->dateTimeBetween($createdAt, 'now');
    //     if($id_emetteur == 3){
    //         $id_destinataire = 1;
    //         $role = Roles_demande::Entreprise;
    //     }else{
    //         $role = Roles_demande::Employe;
    //         $id_destinataire = 3;
    //     }
    //     return [
    //         // ID auto-incrémenté ou généré par Laravel (inutile ici)
    //         'id_emetteur'      => $id_emetteur,
    //         'id_destinataire'  => $id_destinataire,
    //         'montant'          => $this->faker->randomFloat(2, 1000, 100000),
    //         'statut'           => $this->faker->randomElement([Statuts_demande::En_attente, Statuts_demande::Accorde, Statuts_demande::Refuse]),
    //         'motif'            => $this->faker->sentence,
    //         'created_at'             => $createdAt,
    //         'updated_at'             => $updatedAt,
    //         'role'             => $role,
    //     ];
    // }
}
