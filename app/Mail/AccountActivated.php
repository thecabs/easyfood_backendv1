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

    /**
     * Create a new message instance.
     *
     * @param $user
     * @param $compte
     */
    public function __construct($user, $compte)
    {
        $this->user = $user;
        $this->compte = $compte;
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
                    ]);
    }
}
