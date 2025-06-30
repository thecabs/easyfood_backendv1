<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificationDemandeAccorde extends Notification
{
    use Queueable;
    public $demande;
    /**
     * Create a new notification instance.
     */
    public function __construct($demande)
    {
        $this->demande = $demande;
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
            'message' => 'Votre demande de  '. $this->demande->montant.' U a été accordée',
            'demande_id' => $this->demande->id_demande,
            'date' => $this->demande->created_at,
            'de' => $this->demande->destinataire,
            'type' => 'demande',
        ];
    }

}
