<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Compte;
use App\Models\Demande;
use App\Models\Transaction;
use App\Models\TypeTransaction;
use App\Models\User;
use App\Models\VerifRole;
use Illuminate\Support\Facades\Auth;

class dashboardCredit extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $user = User::find($user->id_user);
        $verifRole = new VerifRole();
        //*********** 
        // recuperer les transactions
        //*********** 
        //tout
        $transactions = Transaction::getAll($user->id_user)->latest()->take(5)->get();
        //entrées
        $incomes = Transaction::incomes($user->id_user)->latest()->take(5)->get();
        //sorties
        $expenses = Transaction::expenses($user->id_user)->latest()->take(5)->get();

        // recuperer les demandes
       $demandes = Demande::getAll($user->id_user)->latest()->take(5)->get();

        // recuperer le compte de l'utilisateur
        $compte = $user->compte;

        return response()->json([
            'status' => 'success',
            'message' => 'données récupérées avec succès.',
            'transactions' => $transactions,
            'incomes' => $incomes,
            'expenses' => $expenses,
            'demandes' => $demandes,
            'compte' => [
                'id_compte' => $compte->id_compte,
                'solde' => $compte->solde,
                'created_at' => $compte->created_at,
                'id_user' => $compte->id_user,
                'numero_compte' => $compte->numero_compte,
            ],
        ],200);
    }
}
