<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransactionReceived extends Mailable
{
    use Queueable, SerializesModels;
    public $transaction;
    /**
     * Create a new message instance.
     */
    public function __construct($transaction)
    {
        $this->transaction = $transaction;
    }

    public function build()
    {
        return $this->subject('Crédits recue')->view('emails.transaction_received')->with([
            'transaction' => $this->transaction,
        ]);
    }
}
