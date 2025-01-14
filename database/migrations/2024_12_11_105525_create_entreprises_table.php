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
            $table->text('adresse'); // Adresse complète
            $table->string('logo')->nullable(); // Nouveau champ pour le logo (nullable)
            
            // Relations avec d'autres tables
            $table->unsignedBigInteger('id_assurance')->nullable(); // Relation avec la table assurances
            $table->unsignedBigInteger('id_compte')->nullable(); // Relation avec la table comptes
            $table->unsignedBigInteger('id_gestionnaire')->nullable(); // Relation avec la table users
            
            $table->timestamps();

            // Définition des clés étrangères
            $table->foreign('id_assurance')
                ->references('id_assurance')
                ->on('assurances')
                ->onDelete('cascade'); // Suppression en cascade si l'assurance est supprimée

            $table->foreign('id_compte')
                ->references('id_compte')
                ->on('comptes')
                ->onDelete('set null'); // Met à NULL si le compte est supprimé
            
            $table->foreign('id_gestionnaire')
                ->references('id_user')
                ->on('users')
                ->onDelete('set null'); // Met à NULL si l'utilisateur est supprimé
        });
    }

    public function down()
    {
        Schema::dropIfExists('entreprises');
    }
}
