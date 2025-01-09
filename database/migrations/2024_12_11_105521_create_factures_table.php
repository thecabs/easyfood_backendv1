<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFacturesTable extends Migration
{
    public function up()
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->id('id_facture');
            $table->date('date_facturation');
            $table->decimal('montant', 15, 2);
            $table->string('statut');
            $table->unsignedBigInteger('id_vendeur')->nullable();
            $table->unsignedBigInteger('id_client')->nullable();
 
            $table->timestamps();
            
            $table->foreign('id_vendeur')->references('id_user')->on('users')->onDelete('set null');
            $table->foreign('id_client')->references('id_user')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('factures');
    }
}
