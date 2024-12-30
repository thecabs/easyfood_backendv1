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
     * Effectuer une transaction (crédit ou débit).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validation des données
        $validatedData = $request->validate([
            'numero_compte' => 'required|exists:comptes,numero_compte',
            'montant' => 'required|numeric|min:0.01',
            'type' => 'required|in:credit,debit',
        ]);

        // Vérification des rôles en fonction du type de transaction
        $currentUser = Auth::user();
        if (
            ($validatedData['type'] === 'credit' && !in_array($currentUser->role, ['administrateur', 'superadmin', 'entreprise_gest'])) ||
            ($validatedData['type'] === 'debit' && $currentUser->role !== 'caissiere_gest')
        ) {
            return response()->json(['message' => 'Vous n\'avez pas l\'autorisation pour effectuer cette transaction.'], 403);
        }

        DB::beginTransaction();

        try {
            // Récupérer le compte associé
            $compte = Compte::where('numero_compte', $validatedData['numero_compte'])->firstOrFail();

            // Gestion du débit
            if ($validatedData['type'] === 'debit') {
                if ($compte->solde < $validatedData['montant']) {
                    return response()->json([
                        'message' => 'Fonds insuffisants pour effectuer ce retrait.',
                    ], 400);
                }

                // Déduire le montant du solde du compte
                $compte->solde -= $validatedData['montant'];
            }

            // Gestion du crédit
            if ($validatedData['type'] === 'credit') {
                $compte->solde += $validatedData['montant'];
            }

            // Enregistrer le nouveau solde du compte
            $compte->save();

            // Créer un enregistrement pour la transaction
            $transaction = Transaction::create([
                'numero_compte' => $validatedData['numero_compte'],
                'montant' => $validatedData['montant'],
                'type' => $validatedData['type'],
                'date' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Transaction effectuée avec succès.',
                'transaction' => $transaction,
            ], 201);
        } catch (\Exception $e) {
            // Annuler les modifications en cas d'erreur
            DB::rollBack();

            return response()->json([
                'message' => 'Une erreur est survenue lors du traitement de la transaction.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function effectuerTransaction(Request $request)
{
    // Validation des données
    $validated = $request->validate([
        'numero_compte_gestionnaire' => 'required|exists:comptes,numero_compte',
        'numero_compte_employe' => 'required|exists:comptes,numero_compte',
        'montant' => 'required|numeric|min:0.01',
    ], [
        'numero_compte_gestionnaire.exists' => 'Le compte gestionnaire spécifié n\'existe pas.',
        'numero_compte_employe.exists' => 'Le compte employé spécifié n\'existe pas.',
        'montant.min' => 'Le montant doit être supérieur à zéro.',
    ]);

    DB::beginTransaction();

    try {
        // Récupérer les comptes
        $compteGestionnaire = Compte::where('numero_compte', $validated['numero_compte_gestionnaire'])->first();
        $compteEmploye = Compte::where('numero_compte', $validated['numero_compte_employe'])->first();

        // Vérifier que le gestionnaire a suffisamment de fonds
        if ($compteGestionnaire->solde < $validated['montant']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Solde insuffisant sur le compte du gestionnaire.',
            ], 400);
        }

        // Effectuer la transaction
        $montant = $validated['montant'];

        $compteGestionnaire->solde -= $montant;
        $compteGestionnaire->save();

        $compteEmploye->solde += $montant;
        $compteEmploye->save();

        // Enregistrer la transaction
        $transaction = Transaction::create([
            'numero_compte' => $validated['numero_compte_gestionnaire'],
            'montant' => $montant,
            'date' => now(),
            'type' => 'debit',
        ]);

        Transaction::create([
            'numero_compte' => $validated['numero_compte_employe'],
            'montant' => $montant,
            'date' => now(),
            'type' => 'credit',
        ]);

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Transaction effectuée avec succès.',
            'transaction' => [
                'gestionnaire' => $transaction,
                'employe' => [
                    'numero_compte' => $compteEmploye->numero_compte,
                    'nouveau_solde' => $compteEmploye->solde,
                ],
            ],
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'status' => 'error',
            'message' => 'Une erreur est survenue lors de la transaction.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

}
