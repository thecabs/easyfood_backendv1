<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProduitsTable extends Migration
{
    public function up()
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->id('id_produit');
            $table->string('nom');
            $table->unsignedBigInteger('id_categorie');
            $table->decimal('prix_ifc', 15, 2);
            $table->decimal('prix_shop', 15, 2);
            $table->unsignedBigInteger('id_partenaire')->nullable();
            $table->string('statut');
            $table->string('code_barre')->unique(); // Nouveau champ
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('produits');
    }
}
