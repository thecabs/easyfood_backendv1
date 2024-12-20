<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration{
    public function up()
    {
        Schema::create('lignes_factures', function (Blueprint $table) {
            $table->id('id_ligne_facture');
            $table->unsignedBigInteger('id_facture');
            $table->unsignedBigInteger('id_produit');
            $table->integer('quantite');
            $table->timestamps();

            //$table->foreign('id_facture')->references('id_facture')->on('factures')->onDelete('cascade');
            //$table->foreign('id_produit')->references('id_produit')->on('produits')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('lignes_factures');
    }
};
