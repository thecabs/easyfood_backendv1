<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class TestNotif extends Notification implements ShouldBroadcast
{
    use Queueable;
    /**
     * Create a new notification instance.
     */
    public function __construct(public $user)
    {
        
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['broadcast','database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toDatabase(object $notifiable)
    {
        return [
            'message' => 'Vnouveau message U',
        ];
    }

    public function toBroadcast()
    {
        return new BroadcastMessage([
            'message' => 'Vous avez un nouveau message U',
        ]);
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.'.$this->user->id_user);
    }

    public function broadcastAs()
    {
        return 'new.transaction';
    }
}
