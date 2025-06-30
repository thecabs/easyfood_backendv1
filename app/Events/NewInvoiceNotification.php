<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NewInvoiceNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $factureId;
    public string $message;
    public int $clientId;
    public float $montant;
    public string $shopName;

    public function __construct(int $factureId, int $clientId, float $montant, string $shopName)
    {
        $this->factureId = $factureId;
        $this->clientId = $clientId;
        $this->montant = $montant;
        $this->shopName = $shopName;
        $this->message = "🛒 Nouvelle facture de {$shopName} - {$montant} FCFA. Confirmez avec votre PIN.";

        Log::info('📢 NewInvoiceNotification émis pour facture ID : ' . $factureId . ' vers client ID : ' . $clientId);
    }

    public function broadcastOn(): Channel
    {
        return new Channel("client-{$this->clientId}");
    }

    public function broadcastAs(): string
    {
        return 'operation-initiated';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'facture_id' => $this->factureId,
            'montant' => $this->montant,
            'shop_name' => $this->shopName,
            'timestamp' => now()->toISOString(),
        ];
    }
}