<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\CallSession;

class AgoraService
{
    protected $appId;
    protected $appCertificate;
    protected $baseUrl = 'https://api.agora.io/v1';

    public function __construct()
    {
        $this->appId = config('services.agora.app_id');
        $this->appCertificate = config('services.agora.app_certificate');
    }

    /**
     * Generate RTC token for video call.
     */
    public function generateRtcToken(string $channelName, int $uid, int $privilegeExpiredTs = 0): string
    {
        $token = $this->buildTokenWithUid($channelName, $uid, 1, $privilegeExpiredTs);
        
        Log::info('Generated Agora RTC token', [
            'channel' => $channelName,
            'uid' => $uid,
        ]);

        return $token;
    }

    /**
     * Generate RTM token for chat.
     */
    public function generateRtmToken(string $userAccount): string
    {
        $token = $this->buildTokenWithAccount($userAccount, 2);
        
        Log::info('Generated Agora RTM token', [
            'user_account' => $userAccount,
        ]);

        return $token;
    }

    /**
     * Build token with UID.
     */
    protected function buildTokenWithUid(string $channelName, int $uid, int $role, int $privilegeExpiredTs): string
    {
        $token = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($this->appId . ':' . $this->appCertificate),
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/projects/{$this->appId}/tokens", [
            'uid' => $uid,
            'channel_name' => $channelName,
            'role' => $role,
            'privilege_expired_ts' => $privilegeExpiredTs,
            'token_type' => 1,
        ]);

        if ($token->successful()) {
            return $token->json()['rtc_token'];
        }

        Log::error('Failed to generate Agora token', ['response' => $token->body()]);
        
        throw new \Exception('Failed to generate Agora token: ' . $token->body());
    }

    /**
     * Build token with account name.
     */
    protected function buildTokenWithAccount(string $userAccount, int $role, int $privilegeExpiredTs = 0): string
    {
        $token = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($this->appId . ':' . $this->appCertificate),
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/projects/{$this->appId}/tokens", [
            'user_account' => $userAccount,
            'role' => $role,
            'privilege_expired_ts' => $privilegeExpiredTs,
            'token_type' => 2,
        ]);

        if ($token->successful()) {
            return $token->json()['rtm_token'];
        }

        Log::error('Failed to generate Agora RTM token', ['response' => $token->body()]);
        
        throw new \Exception('Failed to generate Agora RTM token: ' . $token->body());
    }

    /**
     * Generate simple token for demo (client-side generation not recommended for production).
     */
    public function generateSimpleToken(string $channelName, int $uid): array
    {
        // This is a simplified token for demo purposes
        // In production, use the REST API method above
        return [
            'appId' => $this->appId,
            'channel' => $channelName,
            'token' => $this->generateRtcToken($channelName, $uid),
            'uid' => $uid,
        ];
    }

    /**
     * Validate Agora signature (for webhooks).
     */
    public function validateSignature(string $payload, string $signature): bool
    {
        // Implement signature validation for production
        return true; // Placeholder for demo
    }

    /**
     * Get app credentials.
     */
    public function getAppCredentials(): array
    {
        return [
            'appId' => $this->appId,
        ];
    }
}
