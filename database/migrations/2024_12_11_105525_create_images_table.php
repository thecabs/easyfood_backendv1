<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImagesTable extends Migration
{
    public function up()
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id('id_image');
            $table->string('url_photo');
            $table->unsignedBigInteger('id_produit')->nullable();
            $table->timestamps();

            $table->foreign('id_produit')->references('id_produit')->on('produits')->onDelete('cascade');
         });
    }

    public function down()
    {
        Schema::dropIfExists('images');
    }
}
