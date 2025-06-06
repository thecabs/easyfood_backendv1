<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('numero_compte_src')->nullable();
            $table->string('numero_compte_dest')->nullable();
            $table->decimal('montant', 15, 2);
            $table->dateTime('date');
            $table->enum('type', ['credit', 'debit', 'transfert']);
            $table->timestamps();

            // Relations
            $table->foreign('numero_compte_src')
                ->references('numero_compte')
                ->on('comptes')
                ->onDelete('set null');
            $table->foreign('numero_compte_dest')
                ->references('numero_compte')
                ->on('comptes')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
