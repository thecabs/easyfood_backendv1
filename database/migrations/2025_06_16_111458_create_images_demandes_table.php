<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('images_demandes', function (Blueprint $table) {
            $table->id('id_image');
            $table->unsignedBigInteger('id_demande');
            $table->text('url');
            $table->timestamps();

            $table->foreign('id_demande')->references('id_demande')->on('demandes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images_demandes');
    }
};
