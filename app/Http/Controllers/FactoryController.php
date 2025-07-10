<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use App\Models\Demande;
use App\Models\Produit;
use Illuminate\Http\Request;
use App\Models\Roles_demande;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Artisan;
use Database\Seeders\PartenaireShopSeeder;

class FactoryController extends Controller
{
    // assurance entreprises, employe
    public function database1(Request $request){
        $validate = $request->validate([
            'assurance' => ['required','integer','max:100'],
            'entreprise' => ['required','integer','max:100'],
            'employe' => ['required','integer','max:100'],
        ]);
        // Exécuter la méthode personnalisée
        $seeder = app(DatabaseSeeder::class);
        $seeder->runWithParams($validate['assurance'],$validate['entreprise'],$validate['employe']);

        return response()->json(['message' => 'Seeder exécuté avec succès']);
    }
    // shop
    public function database2(Request $request){
        $validate = $request->validate([
            'shop' => ['required','integer','max:100'],
        ]);
        // Exécuter la méthode personnalisée
        $seeder = app(PartenaireShopSeeder::class);
        $seeder->runWithParams($validate['shop']);

        return response()->json(['message' => 'Seeder exécuté avec succès']);
    }
    // demandes
    public function database3(Request $request){
        $validate = $request->validate([
            'id_emetteur' => ['required','integer'],
            'id_destinataire' => ['required','integer'],
            'role' => ['required',new Enum(Roles_demande::class)],
            'nbr' => ['required','integer'],
        ]);
         // Crée une date de création aléatoire entre 6 mois et aujourd’hui
         $createdAt = fake()->dateTimeBetween('-8 months', 'now');

         // updated_at toujours >= created_at
         $updatedAt = fake()->dateTimeBetween($createdAt, 'now');

         Demande::factory()->count($validate['nbr'])->create([
            'id_emetteur'=>$validate['id_emetteur'],
            'id_destinataire'=>$validate['id_destinataire'],
            'role'=>$validate['role'],
            'created_at'=>$createdAt,
            'updated_at'=>$updatedAt,
         ]);



        return response()->json(['message' => 'Seeder exécuté avec succès']);
    }
    // categorie
    public function database4(Request $request){
        $validate = $request->validate([
            'id_shop' => ['required','integer'],
            'nbr' => ['required','integer'],
        ]);

         Categorie::factory()->count($validate['nbr'])->create([
            'id_shop'=>$validate['id_shop'],
         ]);

        return response()->json(['message' => 'Seeder exécuté avec succès']);
    }
    // produit
    public function database5(Request $request){
        $validate = $request->validate([
            'id_shop' => ['required','integer'],
            'id_categorie' => ['required','integer'],
            'nbr' => ['required','integer'],
        ]);

         Produit::factory()->count($validate['nbr'])->create([
            'id_shop'=>$validate['id_shop'],
            'id_categorie'=>$validate['id_categorie'],
         ]);

        return response()->json(['message' => 'Seeder exécuté avec succès']);
    }
    //categorie / produit
    public function database45(Request $request){
        $validate = $request->validate([
            'id_shop' => ['required','integer'],
            'nbr_categorie' => ['required','integer'],
            'nbr_produit' => ['required','integer'],
        ]);
        for($i = 0; $i< $validate['nbr_categorie']; $i++){
            $categorie = Categorie::factory()->create([
                'id_shop'=>$validate['id_shop'],
             ]);
             for($j = 0; $j < $validate['nbr_produit']; $j++){
                 Produit::factory()->create([
                    'id_shop'=>$validate['id_shop'],
                    'id_categorie'=>$categorie->id,
                 ]);
             }
        }

        return response()->json(['message' => 'Seeder exécuté avec succès']);
    }
}
