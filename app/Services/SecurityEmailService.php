<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;

class SecurityEmailService
{
    public static function sendAttackAlert(array $data, array $mcp)
    {
        $subject = "🚨 AI Security Alert - MCP Detected Attack";

        $message = "
AI Security Alert

IP: {$data['ip']}
Email: {$data['email']}

Decision: {$mcp['decision']}
Attack Type: {$mcp['attack_type']}
Risk Score: {$mcp['risk_score']}
Confidence: {$mcp['confidence']}
Recommended Action: {$mcp['recommended_action']}

Time: ".now()."
";

        Mail::raw($message, function ($mail) use ($subject) {
            $mail->to(config('security.alert_email'))
                 ->subject($subject);
        });
    }
}
