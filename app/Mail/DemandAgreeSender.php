<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemandAgreeSender extends Mailable
{
    use Queueable, SerializesModels;
    public $demande;
    /**
     * Create a new message instance.
     */
    public function __construct($demande)
    {
        $this->demande = $demande;
    }

    public function build()
    {
        return $this->subject('Transaction effectuée')->view('emails.demande_agree_sender')->with([
            'demande' => $this->demande,
        ]);
    }
}
