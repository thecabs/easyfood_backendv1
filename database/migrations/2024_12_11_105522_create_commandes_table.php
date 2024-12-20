<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommandesTable extends Migration
{
    public function up()
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id('id_commande');
            $table->date('date_commande');
            $table->string('statut');
            $table->decimal('montant', 15, 2);
            $table->unsignedBigInteger('id_client');
            $table->string('telephone');
            $table->timestamps();

            $table->foreign('id_client')->references('id_user')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('commandes');
    }
}
