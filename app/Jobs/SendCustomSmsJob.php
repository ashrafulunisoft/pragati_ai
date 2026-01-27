<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SendCustomSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $phone;
    protected $message;

    /**
     * Create a new job instance.
     */
    public function __construct(string $phone, string $message)
    {
        $this->phone = $phone;
        $this->message = $message;
    }

    /**
     * Execute job.
     */
    public function handle(): void
    {
        try {
            Log::info('Processing custom SMS job', [
                'phone' => $this->phone,
                'message_length' => strlen($this->message),
                'job_id' => $this->job->getJobId(),
            ]);

            // Use SmsNotificationService to send SMS
            $smsService = new \App\Services\SmsNotificationService();
            $result = $smsService->send($this->phone, $this->message);

            if ($result['success']) {
                Log::info('Custom SMS sent successfully via job', [
                    'phone' => $this->phone,
                    'job_id' => $this->job->getJobId(),
                    'sent_at' => now()->toDateTimeString()
                ]);
            } else {
                throw new \Exception('SMS gateway returned failure: ' . $result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send custom SMS in job', [
                'error' => $e->getMessage(),
                'phone' => $this->phone,
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
        Log::error('Custom SMS job failed permanently', [
            'error' => $exception->getMessage(),
            'phone' => $this->phone,
            'job_id' => $this->job->getJobId() ?? 'unknown',
            'attempts' => $this->attempts(),
        ]);
    }

}
