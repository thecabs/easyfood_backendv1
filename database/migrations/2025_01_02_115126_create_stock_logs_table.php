<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockLogsTable extends Migration
{
    public function up()
    {
        Schema::create('stock_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_stock');
            $table->unsignedBigInteger('id_user'); // Utilisateur qui a effectué l'action
            $table->string('action'); // Action effectuée: "create", "update", "delete"
            $table->json('details')->nullable(); // Détails des modifications
            $table->timestamps();

            // Clés étrangères
            $table->foreign('id_stock')->references('id_stock')->on('stocks')->onDelete('cascade');
            $table->foreign('id_user')->references('id_user')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_logs');
    }
}
