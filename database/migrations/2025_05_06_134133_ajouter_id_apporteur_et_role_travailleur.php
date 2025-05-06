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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [
                'superadmin', 'admin', 'employe', 
                'entreprise_gest', 'shop_gest', 
                'caissiere', 'assurance_gest','travailleur'
            ])->default('employe')->change();
            ;
            $table->unsignedBigInteger('id_apporteur')->nullable();
            $table->foreign('id_apporteur')->references('id_user')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_apporteur']);
            $table->dropColumn('id_apporteur');

        });
    }
};
