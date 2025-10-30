<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendInviteEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $token;

    /**
     * Create a new message instance.
     */
    public function __construct($data, $token)
    {
        $this->data = $data;
        $this->token = $token;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('anonimo@gmail.com', 'Progaming Fileds'),
            replyTo: [new Address('anonimo@gmail.com', 'asdflkj')],
            subject: 'Send Test Email',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // 🔹 URL del frontend (defínela en tu archivo .env)
        $frontendUrl = 'http://localhost:5173';

        // 🔹 Construir el enlace con el token
        $link = "{$frontendUrl}/activar?token={$this->token}";
        return new Content(
            view: 'mails.invite-mail',
            with: [
                'data' => $this->data,
                'link' => $link,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
