<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewTransactionNotification
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public $userId, public $data) {}

    public function broadcastOn()
    {
        return new PrivateChannel('user.'.$this->userId);
    }

    public function broadcastWith()
    {
        return $this->data;
    }

    public function broadcastAs()
    {
        return 'new.transaction';
    }
}
