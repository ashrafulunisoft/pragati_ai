<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Monyxie\Agora\TokenBuilder\TokenFactory;
use Monyxie\Agora\TokenBuilder\AccessControl\Privilege;

class AgoraService
{
    protected $appId;
    protected $appCertificate;
    protected $tokenFactory;

    public function __construct()
    {
        $this->appId = config('services.agora.app_id');
        $this->appCertificate = config('services.agora.app_certificate');
        $this->tokenFactory = new TokenFactory($this->appId, $this->appCertificate);
    }

    /**
     * Generate RTC token for video call.
     */
    public function generateRtcToken(string $channelName, int $uid, int $expireTimeInSeconds = 3600): string
    {
        $privileges = [
            Privilege::JOIN_CHANNEL => time() + $expireTimeInSeconds,
            Privilege::PUBLISH_AUDIO_STREAM => time() + $expireTimeInSeconds,
            Privilege::PUBLISH_VIDEO_STREAM => time() + $expireTimeInSeconds,
        ];

        $token = $this->tokenFactory->create(
            $channelName,
            (string) $uid,
            $privileges,
            time() + $expireTimeInSeconds
        );

        $tokenString = $token->toString();

        Log::info('Generated Agora RTC token', [
            'channel' => $channelName,
            'uid' => $uid,
        ]);

        return $tokenString;
    }

    /**
     * Generate RTM token for chat.
     */
    public function generateRtmToken(string $userAccount): string
    {
        $token = $this->tokenFactory->createRtmToken($userAccount);

        Log::info('Generated Agora RTM token', [
            'user_account' => $userAccount,
        ]);

        return $token->toString();
    }

    /**
     * Generate simple token for demo.
     */
    public function generateSimpleToken(string $channelName, int $uid): array
    {
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
