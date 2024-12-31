<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCaissieresTable extends Migration
{
    public function up()
    {
        Schema::create('caissieres', function (Blueprint $table) {
            $table->id('id_caissiere'); // Clé primaire
            $table->unsignedBigInteger('id_partenaire'); // Relation avec PartenaireShop
            $table->unsignedBigInteger('id_user'); // Relation avec User
            $table->timestamps();

            // Définir les clés étrangères
            $table->foreign('id_partenaire')->references('id_partenaire')->on('partenaire_shops')->onDelete('cascade');
            $table->foreign('id_user')->references('id_user')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('caissieres');
    }
}
