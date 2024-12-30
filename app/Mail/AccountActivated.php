<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountActivated extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $compte;
    public $pin; // Ajouter le PIN

    /**
     * Create a new message instance.
     *
     * @param $user
     * @param $compte
     * @param $pin
     */
    public function __construct($user, $compte, $pin)
    {
        $this->user = $user;
        $this->compte = $compte;
        $this->pin = $pin; // Initialiser le PIN
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Activation de votre compte')
                    ->view('emails.account_activated')
                    ->with([
                        'nom' => $this->user->name,
                        'numeroCompte' => $this->compte->numero_compte,
                        'pin' => $this->pin, // Passer le PIN à la vue
                    ]);
    }
}
