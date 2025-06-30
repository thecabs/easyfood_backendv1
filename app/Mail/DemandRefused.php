<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemandRefused extends Mailable
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

    public function build(){
        return $this->subject('Demande de crédit')->view('emails.demand_refused')->with([
            'demande' => $this->demande,
        ]);
    }
}
