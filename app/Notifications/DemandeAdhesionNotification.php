<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class DemandeAdhesionNotification extends Notification
{
    use Queueable;
    public $employe;
    /**
     * Create a new notification instance.
     */
    public function __construct(User $employe)
    {
        $this->employe = $employe;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("📩 Nouvelle demande d’adhésion à votre entreprise")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Une nouvelle personne a soumis une demande pour rejoindre votre entreprise.")
            ->line("**Nom :** {$this->employe->nom}")
            ->line("**Email :** {$this->employe->email}")
            ->line("**Date de la demande :** " . now()->format('d/m/Y à H:i'))
            ->line("Merci de traiter cette demande rapidement.")
            ->salutation('Cordialement,');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {

        return [
            'message' => 'Nouvelle demande d’adhésion à votre entreprise: mr/mme '.$this->employe->nom,
            'employe_id' => $this->employe->id_user,
            'date' => $this->employe->created_at,
            'de' => $this->employe,
            'statut' => $this->employe->statut,
            'type' => 'demande_adhesion',
        ];
    }
}
