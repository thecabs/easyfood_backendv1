<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountCreatedMailA extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $password;
   

    /**
     * Create a new message instance.
     *
     * @param $user
     * @param $password
   
     */
    public function __construct($user, $password)
    {
        $this->user = $user;
        $this->password = $password;
  
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.account_createda')
            ->subject('Votre compte gestionnaire assurance a été créé')
            ->with([
                'user' => $this->user,
                'password' => $this->password,
                
            ]);
    }
}
