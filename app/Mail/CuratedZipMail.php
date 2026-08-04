<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CuratedZipMail extends Mailable
{
    use Queueable, SerializesModels;

    public $greetingText;
    public $zipFilePath;
    public $zipFileName;

    /**
     * Create a new message instance.
     */
    public function __construct($greetingText, $zipFilePath, $zipFileName)
    {
        $this->greetingText = $greetingText;
        $this->zipFilePath = $zipFilePath;
        $this->zipFileName = $zipFileName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Data Kandidat Terkurasi - ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: '
                <div style="font-family: Arial, sans-serif; padding: 20px; color: #333;">
                    <h2>Halo Tim HRD,</h2>
                    <p style="white-space: pre-wrap;">' . e($this->greetingText) . '</p>
                    <p>Terlampir adalah file ZIP berisi data kandidat yang telah dikurasi/disaring. Silakan unduh dan ekstrak lampiran email ini untuk melihat detail data kandidat beserta kelengkapannya.</p>
                    <hr style="border: 1px solid #eee; margin: 20px 0;">
                    <p style="font-size: 12px; color: #777;">Email ini dikirim otomatis oleh sistem ' . config('app.name') . '</p>
                </div>
            ',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->zipFilePath)
                    ->as($this->zipFileName)
                    ->withMime('application/zip'),
        ];
    }
}
