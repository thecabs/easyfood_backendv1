<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('lignes_commandes', function (Blueprint $table) {
            $table->foreign('id_commande')->references('id_commande')->on('commandes')->onDelete('cascade');
            $table->foreign('id_produit')->references('id_produit')->on('produits')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('lignes_commandes', function (Blueprint $table) {
            $table->dropForeign(['id_commande']);
            $table->dropForeign(['id_produit']);
        });
    }
};
