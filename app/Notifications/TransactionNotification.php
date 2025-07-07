<?php

namespace App\Notifications;

use App\Models\Roles;
use App\Models\transaction as ModelsTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TransactionNotification extends Notification
{
    use Queueable;

    protected $transaction;

    public function __construct(ModelsTransaction $transaction)
    {
        $this->transaction = $transaction;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $subject = 'Nouvelle transaction';
        $message = '';
        $emetteur = null;
        $destinataire = null;

        if($this->transaction->compteEmetteur->emetteur->role === Roles::Admin->value){
            $emetteur = config('app.name');
        }else{
            $emetteur =  $this->transaction->compteEmetteur->emetteur->nom;
        }

        $destinataire = $this->transaction->compteDestinataire->destinataire->nom;

        $message = 'Vous avez reçu ' . $this->transaction->montant . 'U de la part de ' . $emetteur . '.';

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Bonjour ' . $notifiable->nom . ',')
            ->line($message)
            ->line('veuillez vous connecter pour plus de détails:')
            ->line('Merci de votre confiance.');
    }

    public function toDatabase($notifiable)
    {
        $message = '';
        $emetteur = null;
        $destinataire = null;


        if($this->transaction->compteEmetteur->emetteur->role === Roles::Admin->value){
            $emetteur = config('app.name');
        }else{
            $emetteur =  $this->transaction->compteEmetteur->emetteur->nom;
        }

        $destinataire = $this->transaction->compteDestinataire->destinataire->nom;

        $message = 'Vous avez reçu ' . $this->transaction->montant . 'U de la part de ' . $emetteur . '.';


        return [
            'message' => $message,
            'transaction_id' => $this->transaction->id_transaction,
            'date' => $this->transaction->created_at,
            'de' => $this->transaction->compteEmetteur->emetteur,
            'type' => 'transaction',
        ];
    }
}
