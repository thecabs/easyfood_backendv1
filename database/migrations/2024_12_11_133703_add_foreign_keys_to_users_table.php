<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
             $table->foreign('id_entreprise')->references('id_entreprise')->on('entreprises')->onDelete('set null');
            $table->foreign('id_partenaire_shop')->references('id_partenaire')->on('partenaire_shops')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
             $table->dropForeign(['id_assurance']);
            $table->dropForeign(['id_entreprise']);
            $table->dropForeign(['id_partenaire_shop']);
        });
    }
};
