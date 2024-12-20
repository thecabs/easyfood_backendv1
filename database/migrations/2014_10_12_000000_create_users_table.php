<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('id_user'); // Identifiant unique de l'utilisateur
            $table->string('email')->unique(); // Email unique
            $table->string('password'); // Mot de passe
            $table->string('nom'); // Nom de l'utilisateur
            $table->string('tel')->nullable(); // Téléphone, facultatif
            $table->string('quartier')->nullable(); // Quartier, facultatif
            $table->string('ville')->nullable(); // Ville, facultatif
            $table->enum('role', [
                'superadmin', 'administrateur', 'employe', 
                'entreprise_gest', 'partenaire_shop_gest', 
                'caissiere_gest', 'assurance_gest'
            ])->default('employe');
            $table->unsignedBigInteger('id_assurance')->nullable(); // Référence à l'assurance
            $table->unsignedBigInteger('id_entreprise')->nullable(); // Référence à l'entreprise
            $table->unsignedBigInteger('id_partenaire_shop')->nullable(); // Référence au partenaire shop
            $table->enum('statut', ['en_attente', 'actif', 'inactif'])->default('inactif'); // Statut
            $table->timestamps();

            // Foreign keys
            
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}
