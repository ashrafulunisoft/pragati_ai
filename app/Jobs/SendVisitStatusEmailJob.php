<?php

namespace App\Jobs;

use App\Mail\VisitStatusEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendVisitStatusEmailJob implements ShouldQueue
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
            Log::info('Processing visit status email job', [
                'visitor_email' => $this->emailData['visitor_email'] ?? 'N/A',
                'status' => $this->emailData['status'] ?? 'N/A',
                'job_id' => $this->job->getJobId(),
            ]);

            Mail::to($this->emailData['visitor_email'])
                ->send(new VisitStatusEmail($this->emailData));

            Log::info('Visit status email sent successfully via job', [
                'visitor_email' => $this->emailData['visitor_email'],
                'status' => $this->emailData['status'],
                'job_id' => $this->job->getJobId(),
                'sent_at' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send visit status email in job', [
                'error' => $e->getMessage(),
                'visitor_email' => $this->emailData['visitor_email'] ?? 'N/A',
                'job_id' => $this->job->getJobId() ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            // Retry job if it fails (max 3 attempts)
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
        Log::error('Visit status email job failed permanently', [
            'error' => $exception->getMessage(),
            'visitor_email' => $this->emailData['visitor_email'] ?? 'N/A',
            'status' => $this->emailData['status'] ?? 'N/A',
            'job_id' => $this->job->getJobId() ?? 'unknown',
            'attempts' => $this->attempts(),
        ]);
    }
}
