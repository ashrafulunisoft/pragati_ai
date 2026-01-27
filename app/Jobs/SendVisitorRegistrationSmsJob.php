<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SendVisitorRegistrationSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $smsData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $smsData)
    {
        $this->smsData = $smsData;
    }

    /**
     * Execute job.
     */
    public function handle(): void
    {
        try {
            Log::info('Processing visitor registration SMS job', [
                'visitor_phone' => $this->smsData['visitor_phone'] ?? 'N/A',
                'job_id' => $this->job->getJobId(),
            ]);

            // Prepare SMS message
            $message = $this->prepareMessage();

            // Use SmsNotificationService to send SMS
            $smsService = new \App\Services\SmsNotificationService();
            $result = $smsService->send($this->smsData['visitor_phone'], $message);

            if ($result['success']) {
                Log::info('Visitor registration SMS sent successfully via job', [
                    'visitor_phone' => $this->smsData['visitor_phone'],
                    'job_id' => $this->job->getJobId(),
                    'sent_at' => now()->toDateTimeString()
                ]);
            } else {
                throw new \Exception('SMS gateway returned failure: ' . $result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send visitor registration SMS in job', [
                'error' => $e->getMessage(),
                'visitor_phone' => $this->smsData['visitor_phone'] ?? 'N/A',
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
        Log::error('Visitor registration SMS job failed permanently', [
            'error' => $exception->getMessage(),
            'visitor_phone' => $this->smsData['visitor_phone'] ?? 'N/A',
            'job_id' => $this->job->getJobId() ?? 'unknown',
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Prepare SMS message
     */
    protected function prepareMessage(): string
    {
        $visitorName = $this->smsData['visitor_name'] ?? 'Visitor';
        $visitDate = $this->smsData['visit_date'] ?? 'N/A';
        $hostName = $this->smsData['host_name'] ?? 'Host';
        $status = $this->smsData['status'] ?? 'Pending';

        return "Dear {$visitorName}, Your visit to UCB Bank is confirmed for {$visitDate}. Host: {$hostName}. Status: {$status}. Thank you!";
    }

}
