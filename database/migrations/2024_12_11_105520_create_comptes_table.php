<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComptesTable extends Migration
{
    public function up()
    {
        Schema::create('comptes', function (Blueprint $table) {
            $table->id(); // Clé primaire auto-incrémentée
            $table->string('numero_compte')->unique(); // Numéro de compte unique
            $table->decimal('solde', 15, 2)->default(0.00);
            $table->date('date_creation');
            $table->unsignedBigInteger('id_user');
            $table->string('pin'); // Nouveau champ pour le PIN crypté
            $table->timestamps();

            $table->foreign('id_user')->references('id_user')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('comptes');
    }
}
