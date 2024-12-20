<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id('numero_transaction');
            $table->unsignedBigInteger('numero_compte');
            $table->decimal('montant', 15, 2);
            $table->dateTime('date');
            $table->enum('type', ['credit', 'debit']);
            $table->timestamps();

            $table->foreign('numero_compte')->references('numero_compte')->on('comptes')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
