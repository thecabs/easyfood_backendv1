<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Compte;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    /**
     * Effectuer un transfert entre deux comptes.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function transfer(Request $request)
    {
        // Validation des données de la requête
        $validated = $request->validate([
            'numero_compte_src' => 'required|exists:comptes,numero_compte',
            'numero_compte_dest' => 'required|exists:comptes,numero_compte|different:numero_compte_src',
            'montant' => 'required|numeric|min:0.01',
        ]);

        $currentUser = Auth::user();

        // Vérification des autorisations
        if (!in_array($currentUser->role, ['admin', 'superadmin', 'entreprise_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer ce transfert.',
            ], 403);
        }

        DB::beginTransaction();

        try {
            // Récupérer les comptes source et destination
            $compteSrc = Compte::where('numero_compte', $validated['numero_compte_src'])->firstOrFail();
            $compteDest = Compte::where('numero_compte', $validated['numero_compte_dest'])->firstOrFail();

            // Vérifier que le compte source a suffisamment de fonds
            if ($compteSrc->solde < $validated['montant']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Solde insuffisant sur le compte source.',
                ], 400);
            }

            // Déduire le montant du compte source
            $compteSrc->solde -= $validated['montant'];
            $compteSrc->save();

            // Ajouter le montant au compte destination
            $compteDest->solde += $validated['montant'];
            $compteDest->save();

            // Enregistrer la transaction
            $transaction = Transaction::create([
                'numero_compte_src' => $validated['numero_compte_src'],
                'numero_compte_dest' => $validated['numero_compte_dest'],
                'montant' => $validated['montant'],
                'type' => 'transfert',
                'date' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Transfert effectué avec succès.',
                'transaction' => $transaction,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors du transfert.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
