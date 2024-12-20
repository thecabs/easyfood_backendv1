<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('lignes_commandes', function (Blueprint $table) {
            $table->foreign('id_commande', 'fk_lignes_commandes_commande')
                ->references('id_commande')
                ->on('commandes')
                ->onDelete('cascade');

            $table->foreign('id_produit', 'fk_lignes_commandes_produit')
                ->references('id_produit')
                ->on('produits')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('lignes_commandes', function (Blueprint $table) {
            $table->dropForeign('fk_lignes_commandes_commande');
            $table->dropForeign('fk_lignes_commandes_produit');
        });
    }
};
