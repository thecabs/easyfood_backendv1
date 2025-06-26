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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->decimal('montant',20,2);
            $table->unsignedBigInteger('id_compte_emetteur');
            $table->unsignedBigInteger('id_compte_destinataire');
            $table->unsignedBigInteger('id_demande')->nullable();
            $table->enum('type',['remboursement','recharge-admin','recharge-entreprise','recharge-travailleur','recharge-employe','achat'])->default('recharge-admin');
            $table->timestamps();

            $table->foreign('id_compte_emetteur')->references('id_compte')->on('comptes')->onDelete('cascade');
            $table->foreign('id_compte_destinataire')->references('id_compte')->on('comptes')->onDelete('cascade');
            $table->foreign('id_demande')->references('id_demande')->on('demandes')->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
