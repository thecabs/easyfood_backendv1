<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEntreprisesTable extends Migration
{
    public function up()
    {
        Schema::create('entreprises', function (Blueprint $table) {
            $table->id('id_entreprise'); // Clé primaire
            $table->string('nom'); // Nom de l'entreprise
            $table->string('secteur_activite'); // Secteur d'activité
            $table->string('ville'); // Ville
            $table->string('quartier'); // Quartier
            $table->text('adresse'); // Adresse complète de l'entreprise
            $table->unsignedBigInteger('id_assurance'); // Relation avec la table assurances
            $table->unsignedBigInteger('id_gestionnaire')->nullable(); // Relation avec la table users
            $table->timestamps();

            // Contrainte de clé étrangère pour id_assurance
            $table->foreign('id_assurance')
                ->references('id_assurance')
                ->on('assurances')
                ->onDelete('cascade'); // Suppression en cascade

            // Contrainte de clé étrangère pour id_user
            $table->foreign('id_gestionnaire')
                ->references('id_user')
                ->on('users')
                ->onDelete('set null'); // Met à NULL si l'utilisateur est supprimé
        });
    }

    public function down()
    {
        Schema::dropIfExists('entreprises'); // Supprime la table entreprises
    }
}
