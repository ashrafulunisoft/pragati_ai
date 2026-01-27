<?php

namespace App\Jobs;

use App\Mail\VisitorRegistrationEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendVisitorRegistrationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $emailData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $emailData)
    {
        $this->emailData = $emailData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Processing visitor registration email job', [
                'visitor_email' => $this->emailData['visitor_email'] ?? 'N/A',
                'job_id' => $this->job->getJobId(),
            ]);

            Mail::to($this->emailData['visitor_email'])
                ->send(new VisitorRegistrationEmail($this->emailData));

            Log::info('Visitor registration email sent successfully via job', [
                'visitor_email' => $this->emailData['visitor_email'],
                'job_id' => $this->job->getJobId(),
                'sent_at' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send visitor registration email in job', [
                'error' => $e->getMessage(),
                'visitor_email' => $this->emailData['visitor_email'] ?? 'N/A',
                'job_id' => $this->job->getJobId() ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            // Retry the job if it fails (max 3 attempts)
            if ($this->attempts() < 3) {
                $this->release(60); // Release for 60 seconds and retry
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Visitor registration email job failed permanently', [
            'error' => $exception->getMessage(),
            'visitor_email' => $this->emailData['visitor_email'] ?? 'N/A',
            'job_id' => $this->job->getJobId() ?? 'unknown',
            'attempts' => $this->attempts(),
        ]);
    }
}
