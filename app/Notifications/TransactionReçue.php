<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TransactionReçue extends Notification 
{
    use Queueable;
    public $transaction;
    /**
     * Create a new notification instance.
     */
    public function __construct($transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toDatabase(object $notifiable)
    {
        return [
            'message' => 'Vous avez reçu '. $this->transaction->montant.' U',
            'transaction_id' => $this->transaction->id,
            'date' => $this->transaction->created_at,
            'de' => $this->transaction->compteEmetteur->user->nom,
            'type' => 'transaction',
        ];
    }




}
