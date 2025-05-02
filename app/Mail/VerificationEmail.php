<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $verificationCode;

    public function __construct($verificationCode)
    {
        $this->verificationCode = $verificationCode;
    }

    public function build()
    {
        return $this->subject('Verify Your Email Address')
                    ->markdown('emails.verification')
                    ->with([
                        'code' => $this->verificationCode,
                        'expires' => now()->addHours(24)->format('M d, Y H:i')
                    ]);
    }
}