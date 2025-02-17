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
            // On passe ici à un ENUM pour distinguer fonds et transmit, ou vous pouvez utiliser un string
            $table->enum('type', ['fonds', 'transmit'])->default('fonds');
            // Le statut peut aussi être étendu selon le type de demande (exemple ici)
            $table->enum('statut', ['en attente', 'validé', 'refusé', 'accordé'])->default('en attente');
            // Champ motif pour stocker le motif en cas de refus (nullable)
            $table->string('motif')->nullable();
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