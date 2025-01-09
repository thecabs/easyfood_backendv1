<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
             $table->foreign('id_entreprise')->references('id_entreprise')->on('entreprises')->onDelete('cascade');
            $table->foreign('id_shop')->references('id_shop')->on('partenaire_shops')->onDelete('cascade');
            $table->foreign('id_assurance')  ->references('id_assurance') ->on('assurances')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
             $table->dropForeign(['id_assurance']);
            $table->dropForeign(['id_entreprise']);
            $table->dropForeign(['id_shop']);
            $table->dropForeign(['id_assurance']);

        });
    }
};
