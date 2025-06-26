<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class TransactionReçue extends Notification implements ShouldBroadcast
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
        return ['database','broadcast'];
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
            'transaction_type' => $this->transaction->type,
        ];
    }

    public function toBroadcast()
    {
        return new BroadcastMessage([
            'message' => 'Vous avez reçu '. $this->transaction->montant.' U',
            'transaction_id' => $this->transaction->id,
            'date' => $this->transaction->created_at,
            'de' => $this->transaction->compteEmetteur->user->nom,
            'transaction_type' => $this->transaction->type,
        ]);
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.'.$this->transaction->compteDestination->user->id);
    }

    public function broadcastAs()
    {
        return 'new.transaction';
    }
}
