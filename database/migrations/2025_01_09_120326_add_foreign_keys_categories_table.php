<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedBigInteger('id_shop')->nullable();  
            $table->foreign('id_shop')->references('id_shop')->on('partenaire_shops')->onDelete('set null');  
        });
    }

    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['id_shop']);
            $table->dropColumn('id_shop');    
        });
    }
};
