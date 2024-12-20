<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployesTable extends Migration
{
    public function up()
    {
        Schema::create('employes', function (Blueprint $table) {
            $table->id('id_employe');
            $table->unsignedBigInteger('id_entreprise');
            $table->unsignedBigInteger('id_user');
            $table->timestamps();

             
        });
    }

    public function down()
    {
        Schema::dropIfExists('employes');
    }
}
