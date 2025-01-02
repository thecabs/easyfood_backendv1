<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartenaireShopsTable extends Migration
{
    public function up()
    {
        Schema::create('partenaire_shops', function (Blueprint $table) {
            $table->id('id_partenaire');
            $table->unsignedBigInteger('id_gestionnaire')->nullable(); // ID utilisateur
            $table->string('nom');
            $table->string('adresse');
            $table->string('ville');
            $table->string('quartier');
            $table->string('logo')->nullable(); // Nouveau champ pour le logo
            $table->timestamps();

            // Clé étrangère vers la table users
            $table->foreign('id_gestionnaire')->references('id_user')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('partenaire_shops');
    }
}


 