<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPassword extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $resetUrl;

    /**
     * Create a new message instance.
     *
     * @param array $userData
     * @param string $resetUrl
     * @return void
     */
    public function __construct($userData, $resetUrl)
    {
        $this->name = $userData['name'] ?? 'Khách Hàng';
        $this->resetUrl = $resetUrl;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Đặt Lại Mật Khẩu')
                    ->priority(1) // High priority
                    ->view('emails.reset-password')
                    ->with([
                        'name' => $this->name,
                        'resetUrl' => $this->resetUrl,
                    ]);
    }
}
