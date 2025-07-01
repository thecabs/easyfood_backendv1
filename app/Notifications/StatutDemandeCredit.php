<?php

namespace App\Notifications;

use App\Models\Demande;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class StatutDemandeCredit extends Notification implements  ShouldBroadcast
{
    use Queueable;
    public $demande;
    /**
     * Create a new notification instance.
     */
    public function __construct(Demande $demande)
    {
        $this->demande = $demande;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast'];
    }
    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("Mise à jour de votre demande de crédit")
            ->line("Votre demande de {$this->demande->montant} U a été : {$this->demande->statut}")
            ->action('Voir la demande', url("/demandes/{$this->demande->id}"))
            ->line('Merci de votre confiance.');
    }

    public function toDatabase(object $notifiable)
    {
        return [
            'message' => 'Votre demande de  '. $this->demande->montant.' U a été accordée',
            'demande_id' => $this->demande->id_demande,
            'date' => $this->demande->created_at,
            'de' => $this->demande->destinataire,
            'type' => 'demande',
        ];
    }
    
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'demande_id' => $this->demande->id,
            'statut' => $this->demande->statut,
            'message' => "Votre demande de crédit a été : {$this->demande->statut}"
        ]);
    }
}
