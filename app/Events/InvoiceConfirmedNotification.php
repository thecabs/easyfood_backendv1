<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class InvoiceConfirmedNotification implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public int $vendeurId;
    public string $shopName;
    public string $fileName;
    public int $factureId;
    public float $montant;

    public function __construct(int $vendeurId, string $shopName, string $fileName, int $factureId, float $montant)
    {
        $this->vendeurId = $vendeurId;
        $this->shopName = $shopName;
        $this->fileName = $fileName;
        $this->factureId = $factureId;
        $this->montant = $montant;

        Log::info('📢 InvoiceConfirmedNotification émis pour vendeur ID : ' . $vendeurId . ' - Facture ID : ' . $factureId);
    }

    public function broadcastOn(): Channel
    {
        return new Channel("cashier-{$this->vendeurId}");
    }

    public function broadcastAs(): string
    {
        return 'invoice-confirmed';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => "✅ Paiement confirmé - Facture #{$this->factureId} - {$this->montant} FCFA",
            'pdf_url' => url("invoices/{$this->shopName}/{$this->fileName}"),
            'facture_id' => $this->factureId,
            'montant' => $this->montant,
            'shop_name' => $this->shopName,
            'file_name' => $this->fileName,
            'timestamp' => now()->toISOString(),
        ];
    }
}