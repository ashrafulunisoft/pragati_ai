<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Jobs\SendVisitorRegistrationSmsJob;
use App\Jobs\SendVisitStatusSmsJob;

class SmsNotificationService
{
    /**
     * Send SMS immediately (synchronous)
     *
     * @param string $phone
     * @param string $message
     * @return array ['success' => bool, 'message' => string]
     */
    public function send(string $phone, string $message): array
    {
        try {
            Log::info('Sending SMS', [
                'phone' => $phone,
                'message_length' => strlen($message),
                'sent_by' => Auth::user()->name ?? 'System'
            ]);

            // Check if SMS is enabled
            if (!config('sms.enabled')) {
                Log::warning('SMS is disabled in config', ['phone' => $phone]);
                return [
                    'success' => false,
                    'message' => 'SMS is disabled'
                ];
            }

            // Get SMS provider
            $provider = config('sms.provider', 'default');

            // Send SMS based on provider
            switch ($provider) {
                case 'nexmo':
                    $result = $this->sendViaNexmo($phone, $message);
                    break;
                case 'twilio':
                    $result = $this->sendViaTwilio($phone, $message);
                    break;
                case 'bulk':
                    $result = $this->sendViaBulkSMS($phone, $message);
                    break;
                default:
                    $result = $this->sendDefault($phone, $message);
                    break;
            }

            if ($result['success']) {
                Log::info('SMS sent successfully', [
                    'phone' => $phone,
                    'provider' => $provider,
                    'sent_at' => now()->toDateTimeString()
                ]);
            } else {
                Log::error('Failed to send SMS', [
                    'phone' => $phone,
                    'provider' => $provider,
                    'error' => $result['message']
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Error sending SMS', [
                'error' => $e->getMessage(),
                'phone' => $phone,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Send SMS via Nexmo/Vonage
     */
    protected function sendViaNexmo(string $phone, string $message): array
    {
        try {
            $apiKey = config('sms.nexmo.api_key');
            $apiSecret = config('sms.nexmo.api_secret');
            $from = config('sms.nexmo.sms_from', config('sms.from'));

            if (!$apiKey || !$apiSecret) {
                throw new \Exception('Nexmo API credentials not configured');
            }

            $response = \Illuminate\Support\Facades\Http::asForm()->post('https://rest.nexmo.com/sms/json', [
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
                'to' => $phone,
                'from' => $from,
                'text' => $message,
            ]);

            $data = $response->json();

            if ($response->successful() && isset($data['messages'][0]['status']) && $data['messages'][0]['status'] == '0') {
                return [
                    'success' => true,
                    'message' => 'SMS sent via Nexmo',
                    'message_id' => $data['messages'][0]['message-id'] ?? null
                ];
            }

            return [
                'success' => false,
                'message' => $data['messages'][0]['error-text'] ?? 'Unknown error'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Send SMS via Twilio
     */
    protected function sendViaTwilio(string $phone, string $message): array
    {
        try {
            $sid = config('sms.twilio.sid');
            $token = config('sms.twilio.token');
            $from = config('sms.twilio.from', config('sms.from'));

            if (!$sid || !$token) {
                throw new \Exception('Twilio API credentials not configured');
            }

            $response = \Illuminate\Support\Facades\Http::asForm()->post(
                "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json",
                [
                    'From' => $from,
                    'To' => $phone,
                    'Body' => $message,
                ],
                function ($request) use ($sid, $token) {
                    $request->withBasicAuth($sid, $token);
                }
            );

            $data = $response->json();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'SMS sent via Twilio',
                    'message_id' => $data['sid'] ?? null
                ];
            }

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Unknown error'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Send SMS via BulkSMS BD
     */
    protected function sendViaBulkSMS(string $phone, string $message): array
    {
        try {
            $apiKey = config('sms.bulk.api_key');
            $senderId = config('sms.bulk.sender_id', config('sms.from'));

            if (!$apiKey) {
                throw new \Exception('BulkSMS API key not configured');
            }

            $response = \Illuminate\Support\Facades\Http::get('https://bulksmsbd.net/api/smsapi', [
                'api_key' => $apiKey,
                'senderid' => $senderId,
                'message' => $message,
                'type' => 'text',
                'number' => $phone,
            ]);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true);

                // Check if SMS was submitted successfully
                if (isset($responseData['response_code']) && $responseData['response_code'] == 202) {
                    return [
                        'success' => true,
                        'message' => 'SMS sent via BulkSMS BD',
                        'message_id' => $responseData['message_id'] ?? null
                    ];
                }
            }

            return [
                'success' => false,
                'message' => $response->body() ?? 'Unknown error'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Default SMS (log only - for development)
     */
    protected function sendDefault(string $phone, string $message): array
    {
        Log::info('SMS not sent (development mode)', [
            'phone' => $phone,
            'message' => $message
        ]);

        return [
            'success' => true,
            'message' => 'SMS logged only (development mode)'
        ];
    }

    /**
     * Send visitor registration SMS (queued)
     *
     * @param array $data
     * @return bool - Returns true immediately (job dispatched)
     */
    public function sendVisitorRegistrationSms(array $data): bool
    {
        try {
            Log::info('Dispatching visitor registration SMS job', [
                'visitor_phone' => $data['visitor_phone'] ?? 'N/A',
                'visitor_name' => $data['visitor_name'] ?? 'N/A',
                'visit_date' => $data['visit_date'] ?? 'N/A',
                'sent_by' => Auth::user()->name ?? 'System'
            ]);

            // Dispatch job to queue for async processing
            SendVisitorRegistrationSmsJob::dispatch($data);

            Log::info('Visitor registration SMS job dispatched successfully', [
                'visitor_phone' => $data['visitor_phone'],
                'visit_date' => $data['visit_date']
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to dispatch visitor registration SMS job', [
                'error' => $e->getMessage(),
                'visitor_phone' => $data['visitor_phone'] ?? 'N/A',
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Send visit status update SMS (queued)
     *
     * @param array $data
     * @return bool - Returns true immediately (job dispatched)
     */
    public function sendVisitStatusSms(array $data): bool
    {
        try {
            Log::info('Dispatching visit status SMS job', [
                'visitor_phone' => $data['visitor_phone'] ?? 'N/A',
                'status' => $data['status'] ?? 'N/A',
                'sent_by' => Auth::user()->name ?? 'System'
            ]);

            // Dispatch job to queue for async processing
            SendVisitStatusSmsJob::dispatch($data);

            Log::info('Visit status SMS job dispatched successfully', [
                'visitor_phone' => $data['visitor_phone'],
                'status' => $data['status']
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to dispatch visit status SMS job', [
                'error' => $e->getMessage(),
                'visitor_phone' => $data['visitor_phone'] ?? 'N/A',
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Send custom SMS notification (queued)
     *
     * @param string $phone
     * @param string $message
     * @return bool - Returns true immediately (job dispatched)
     */
    public function sendCustomSms(string $phone, string $message): bool
    {
        try {
            Log::info('Preparing to dispatch custom SMS', [
                'phone' => $phone,
                'message_length' => strlen($message),
                'sent_by' => Auth::user()->name ?? 'System'
            ]);

            // Create job for custom SMS
            $job = new \App\Jobs\SendCustomSmsJob($phone, $message);
            dispatch($job);

            Log::info('Custom SMS job dispatched successfully', [
                'phone' => $phone
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to dispatch custom SMS job', [
                'error' => $e->getMessage(),
                'phone' => $phone,
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Send bulk SMS notifications (queued)
     *
     * @param array $phones
     * @param string $message
     * @return int - Number of jobs dispatched
     */
    public function sendBulkSms(array $phones, string $message): int
    {
        $dispatchedCount = 0;

        Log::info('Preparing to dispatch bulk SMS', [
            'phone_count' => count($phones),
            'message_length' => strlen($message),
            'sent_by' => Auth::user()->name ?? 'System'
        ]);

        foreach ($phones as $phone) {
            try {
                $job = new \App\Jobs\SendCustomSmsJob($phone, $message);
                dispatch($job);
                $dispatchedCount++;
            } catch (\Exception $e) {
                Log::error('Failed to dispatch SMS to phone', [
                    'error' => $e->getMessage(),
                    'phone' => $phone,
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        Log::info('Bulk SMS dispatching completed', [
            'dispatched_count' => $dispatchedCount,
            'total' => count($phones)
        ]);

        return $dispatchedCount;
    }
}
