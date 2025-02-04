<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up()
    {
        Schema::create('demandes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_entreprise');          
             $table->decimal('montant', 10, 2);
            $table->enum('statut', ['en attente', 'validé'])->default('en attente');
            $table->timestamps();
            $table->foreign('id_user')->references('id_user')->on('users')->onDelete('cascade');
            $table->foreign('id_entreprise')->references('id_entreprise')->on('entreprises')->onDelete('cascade');


        });
    }

    public function down()
    {
        Schema::dropIfExists('demandes');
    }
};