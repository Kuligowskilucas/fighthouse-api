<?php

namespace App\Mail;

use App\Models\Mensalidade;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LembreteVencimento extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Mensalidade $mensalidade, public readonly string $pixChave, public readonly bool $atrasada = false) 
    {

    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Lembrete de vencimento — Fight House Club',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.lembrete-vencimento',
        );
    }
}