<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemandSent extends Mailable
{
    use Queueable, SerializesModels;
    public $user, $demande;
    /**
     * Create a new message instance.
     */
    public function __construct($demande)
    {
        $this->demande = $demande;
    }

    /**
     * Get the message envelope.
     */
    public function build(){
        return $this->subject('Demande de crédit')->view('emails.demand_received')->with([
            'demande' => $this->demande,
        ]);
    }
}
