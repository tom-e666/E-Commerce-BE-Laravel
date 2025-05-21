<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $verificationUrl;

    /**
     * Create a new message instance.
     *
     * @param array $userData
     * @param string $verificationUrl
     * @return void
     */
    public function __construct($userData, $verificationUrl)
    {
        $this->name = $userData['name'] ?? 'Valued Customer';
        $this->verificationUrl = $verificationUrl;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Verify Your Email Address')
                    ->priority(1) // High priority
                    ->view('emails.verify-email')
                    ->with([
                        'name' => $this->name,
                        'verificationUrl' => $this->verificationUrl,
                    ]);
    }
}