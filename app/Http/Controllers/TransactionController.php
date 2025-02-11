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
    public function operation(Request $request)
    {
        // Validation de la requête avec les deux numéros de compte, le montant et le type d'opération
        $validated = $request->validate([
            'numero_compte_src'  => 'required|exists:comptes,numero_compte',
            'numero_compte_dest' => 'required|exists:comptes,numero_compte|different:numero_compte_src',
            'montant'            => 'required|numeric|min:0.01',
            'type'               => 'required|string|in:debit,credit',
        ]);
    
        // Vérification des autorisations (adapter selon vos besoins)
        $currentUser = Auth::user();
        if (!in_array($currentUser->role, ['admin', 'superadmin', 'entreprise_gest'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette opération.'
            ], 403);
        }
    
        DB::beginTransaction();
    
        try {
            // Récupération des comptes source et destination
            $compteSrc  = Compte::where('numero_compte', $validated['numero_compte_src'])->firstOrFail();
            $compteDest = Compte::where('numero_compte', $validated['numero_compte_dest'])->firstOrFail();
    
            // Traitement selon le type d'opération
            if ($validated['type'] === 'debit') {
                // Pour un débit : le compte source est débité et le compte destination est crédité
                if ($compteSrc->solde < $validated['montant']) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Solde insuffisant sur le compte source pour effectuer un débit.'
                    ], 400);
                }
    
                $compteSrc->solde  -= $validated['montant'];
                $compteDest->solde += $validated['montant'];
            } elseif ($validated['type'] === 'credit') {
                // Pour un crédit : le compte destination est débité et le compte source est crédité
                if ($compteDest->solde < $validated['montant']) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Solde insuffisant sur le compte destination pour effectuer un crédit.'
                    ], 400);
                }
    
                $compteDest->solde -= $validated['montant'];
                $compteSrc->solde  += $validated['montant'];
            }
    
            // Sauvegarde des modifications sur les comptes
            $compteSrc->save();
            $compteDest->save();
    
            // Enregistrement de la transaction
            $transaction = Transaction::create([
                'numero_compte_src'  => $validated['numero_compte_src'],
                'numero_compte_dest' => $validated['numero_compte_dest'],
                'montant'            => $validated['montant'],
                'type'               => $validated['type'],
                'date'               => now(),
            ]);
    
            DB::commit();
    
            return response()->json([
                'status'      => 'success',
                'message'     => 'Opération effectuée avec succès.',
                'transaction' => $transaction,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
    
            return response()->json([
                'status'  => 'error',
                'message' => 'Une erreur est survenue lors de l\'opération.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    
    
}
