<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Models\TypeTransaction;

class TransactionObserver
{
    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction)
    {
        if ($transaction->type == TypeTransaction::RECHARGEADMIN->value) {
            // Crédit compte compteDestinataire
            $compteDestinataire = $transaction->compteDestinataire;
            $compteDestinataire->solde += $transaction->montant;
            $compteDestinataire->save();
        } else {
            //verification du solde
            if ($transaction->compteEmetteur->solde < $transaction->montant) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Solde insuffisant pour effectuer cette transaction.'
                ], 400);
            }

            // Débit compte émetteur
            $compteEmetteur = $transaction->compteEmetteur;
            $compteEmetteur->solde -= $transaction->montant;
            $compteEmetteur->save();

            // Crédit compte compteDestinataire
            $compteDestinataire = $transaction->compteDestinataire;
            $compteDestinataire->solde += $transaction->montant;
            $compteDestinataire->save();
        }
    }

    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        //
    }

    /**
     * Handle the Transaction "deleted" event.
     */
    public function deleted(Transaction $transaction): void
    {
        //
    }

    /**
     * Handle the Transaction "restored" event.
     */
    public function restored(Transaction $transaction): void
    {
        //
    }

    /**
     * Handle the Transaction "force deleted" event.
     */
    public function forceDeleted(Transaction $transaction): void
    {
        //
    }
}
