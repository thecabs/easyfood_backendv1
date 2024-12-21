<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssurancesTable extends Migration
{
    public function up()
    {
        Schema::create('assurances', function (Blueprint $table) {
            $table->id('id_assurance'); // Clé primaire
            $table->unsignedBigInteger('id_gestionnaire')->nullable(); // Référence à l'utilisateur
            $table->string('code_ifc');
            $table->string('libelle'); // Nom de l'assurance
            $table->timestamps();

            // Définir la clé étrangère
            $table->foreign('id_gestionnaire')->references('id_user')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('assurances');
    }
}
