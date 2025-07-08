<?php

namespace App\Events;

use App\Models\Roles;
use App\Models\Demande;
use App\Models\Roles_demande;
use App\Models\Statuts_demande;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class DemandeEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $notification;
    public $id_destinataire_event;
    /**
     * Create a new event instance.
     */
    public function __construct(DatabaseNotification $notification, int $id_destinataire_event)
    {
        $this->notification = $notification;
        $this->id_destinataire_event = $id_destinataire_event;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->id_destinataire_event),
        ];
    }
    public function broadcastAs()
    {
        return 'notification-event';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(){


        return [
            'notification'=>$this->notification,
        ];
    }
}
