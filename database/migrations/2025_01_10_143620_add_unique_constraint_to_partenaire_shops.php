<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueConstraintToPartenaireShops extends Migration
{
    public function up()
    {
        Schema::table('partenaire_shops', function (Blueprint $table) {
            // Ajouter une contrainte unique sur 'nom', 'ville', et 'quartier'
            $table->unique(['nom', 'ville', 'quartier'], 'unique_nom_ville_quartier');
        });
    }

    public function down()
    {
        Schema::table('partenaire_shops', function (Blueprint $table) {
            // Supprimer la contrainte unique
            $table->dropUnique('unique_nom_ville_quartier');
        });
    }
}
