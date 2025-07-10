<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class DemandeAdhesionRejeteNotification extends Notification
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
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $this->employe->load('entreprise');
        return (new MailMessage)
            ->subject("📩 Demande d'adhesion")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre demande d'adhesion a l'entreprise {$this->employe->entreprise->nom} a été rejétée.")
            ->line("**Date :** " . now()->format('d/m/Y à H:i'))
            ->salutation('Cordialement,');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
