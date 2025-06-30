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
        Schema::create('demandes', function (Blueprint $table) {
            $table->id('id_demande');
            $table->unsignedBigInteger('id_emetteur')->nullable();
            $table->unsignedBigInteger('id_destinataire')->nullable();
            $table->decimal('montant',20,2);
            $table->text('motif');
            $table->enum('role', ['admin', 'employe', 'entreprise_gest','shop_gest'])->default('admin');
            $table->enum('statut', ['validé', 'en attente', 'accordé', 'refusé','annulé'])->default('en attente');
            $table->timestamps();

            $table->foreign('id_emetteur')->references('id_user')->on('users')->onDelete('SET NULL');
            $table->foreign('id_destinataire')->references('id_user')->on('users')->onDelete('SET NULL');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandes');
    }
};
