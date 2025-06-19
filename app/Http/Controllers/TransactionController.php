<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function index(){
        $user = Auth::user();
        $user = User::find($user->id_user);
        $transactions = Transaction::where('id_compte_emetteur',$user->compte->id_compte)
        ->orWhere('id_compte_destinataire',$user->compte->id_compte)
        ->with([
            'compteEmetteur'=>function($query){
                $query->select('id_compte','id_user','numero_compte','solde','created_at','updated_at')->with(['user'=>function($query){
                    $query->select('id_user','photo_profil','nom','email','tel','ville','quartier','id_shop','id_entreprise')->with(['entreprise:nom,id_entreprise,ville,quartier','shop:nom,id_shop,ville,quartier']);
                }]);
            },
            'compteDestinataire'=>function($query){
                $query->select('id_compte','id_user','numero_compte','solde','created_at','updated_at')->with(['user'=>function($query){
                    $query->select('id_user','photo_profil','nom','email','tel','ville','quartier','id_shop','id_entreprise')->with(['entreprise:nom,id_entreprise,ville,quartier','shop:nom,id_shop,ville,quartier']);
                }]);
            }])->get();

        return response()->json([
            'status' => "success",
            'data' => $transactions,
            'message' => "transactions récupérée avec succès!"
        ],200);
    }
}
