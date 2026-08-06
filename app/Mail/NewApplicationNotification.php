<?php

namespace App\Mail;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Job;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

use Illuminate\Contracts\Queue\ShouldQueue;

class NewApplicationNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Candidate $candidate;
    public Job $job;
    public Application $application;

    public function __construct(Candidate $candidate, Job $job, Application $application)
    {
        $this->candidate = $candidate;
        $this->job = $job;
        $this->application = $application;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔔 Lamaran Baru: ' . $this->candidate->name . ' — ' . $this->job->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.application.new-application-hr',
        );
    }

    public function attachments(): array
    {
        $attachments = [];
        
        // Only attach if MAIL_INCLUDE_FULL_DATA is true to avoid giant emails when not needed
        if (filter_var(env('MAIL_INCLUDE_FULL_DATA', false), FILTER_VALIDATE_BOOLEAN)) {
            $mediaItems = $this->application->getMedia('documents');
            foreach ($mediaItems as $media) {
                $attachments[] = \Illuminate\Mail\Mailables\Attachment::fromPath($media->getPath())
                    ->as($media->file_name)
                    ->withMime($media->mime_type);
            }
        }
        
        return $attachments;
    }
}
