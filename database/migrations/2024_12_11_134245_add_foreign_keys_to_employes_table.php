<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToEmployesTable extends Migration
{
    public function up()
    {
        Schema::table('employes', function (Blueprint $table) {
            $table->foreign('id_entreprise')->references('id_entreprise')->on('entreprises')->onDelete('cascade');
            $table->foreign('id_user')->references('id_user')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('employes', function (Blueprint $table) {
            $table->dropForeign(['id_entreprise']);
            $table->dropForeign(['id_user']);
        });
    }
}
