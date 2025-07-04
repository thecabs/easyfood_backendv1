<?php

namespace Database\Seeders;

use App\Models\Demande;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DemandeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Demande::factory()->count(150)->create();
    }
}
