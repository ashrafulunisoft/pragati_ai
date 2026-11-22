<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use App\Services\McpSecurityService;

/**
 * Tests for the decision-fusion fix (Problem 1).
 *
 * The AI verdict must have genuine authority:
 *  - AI MALICIOUS in the 50-79 band must BLOCK (previously downgraded to SUSPICIOUS)
 *  - AI can raise the risk score but never lower the rule floor
 *  - AI SAFE leniency is bounded (step-up only, never a full allow)
 *
 * All AI responses are faked with Http::fake() - no live API calls.
 */
class McpDecisionFusionTest extends TestCase
{
    /**
     * Fake the MiniMax API to return the given JSON verdict.
     */
    private function fakeAiResponse(array $verdict, int $status = 200): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode($verdict)]],
                ],
            ], $status),
        ]);
    }

    /**
     * P1-a: AI MALICIOUS verdict with rule score 55 MUST end as MALICIOUS.
     * (Old code downgraded this to SUSPICIOUS, which never blocked - the core Problem 1 defect.)
     */
    public function test_ai_malicious_in_medium_band_blocks(): void
    {
        $this->fakeAiResponse([
            'decision' => 'MALICIOUS',
            'attack_type' => 'credential_stuffing',
            'risk_score' => 60,
            'confidence' => 0.9,
            'recommended_action' => 'block_ip',
        ]);

        $result = McpSecurityService::analyzeLogin([
            'ip' => '10.99.0.1',
            'email' => 'fusion1@test.com',
            'ip_attempts' => 5,   // rule: 30
            'email_attempts' => 3, // rule: 25 => rule score 55
        ]);

        $this->assertSame('MALICIOUS', $result['decision']);
        $this->assertSame('F2_ai_escalation', $result['fusion']);
        $this->assertSame('block_ip', $result['recommended_action']);
        $this->assertTrue($result['ai_available']);
        $this->assertSame('MALICIOUS', $result['ai_decision']);
        // Fused score = max(55, 60) = 60
        $this->assertSame(60, $result['risk_score']);
    }

    /**
     * P1-b: AI cannot LOWER the effective score below the rule floor.
     * AI answers SAFE with risk_score 5 while rules say 90 -> must stay MALICIOUS/90.
     */
    public function test_ai_cannot_lower_rule_floor(): void
    {
        $this->fakeAiResponse([
            'decision' => 'SAFE',
            'attack_type' => 'normal_user',
            'risk_score' => 5,
            'confidence' => 0.99,
            'recommended_action' => 'monitor',
        ]);

        $result = McpSecurityService::analyzeLogin([
            'ip' => '10.99.0.2',
            'email' => 'fusion2@test.com',
            'ip_attempts' => 10, // 50
            'email_attempts' => 5, // 40 => rule score 90
        ]);

        $this->assertSame('MALICIOUS', $result['decision']);
        $this->assertSame(90, $result['risk_score']);
        $this->assertSame('F1_rule_guardrail_high_score', $result['fusion']);
        $this->assertSame('block_ip', $result['recommended_action']);
    }

    /**
     * P1-c: AI CAN raise the score - AI risk_score 100 with rule score 30 must end MALICIOUS.
     */
    public function test_ai_can_raise_score_to_high(): void
    {
        $this->fakeAiResponse([
            'decision' => 'SUSPICIOUS',
            'attack_type' => 'bot_attack',
            'risk_score' => 100,
            'confidence' => 0.7,
            'recommended_action' => 'monitor',
        ]);

        $result = McpSecurityService::analyzeLogin([
            'ip' => '10.99.0.3',
            'email' => 'fusion3@test.com',
            'ip_attempts' => 5,    // rule: 30
            'email_attempts' => 0,
        ]);

        $this->assertSame('MALICIOUS', $result['decision']);
        $this->assertSame(100, $result['risk_score']);
        $this->assertSame('block_ip', $result['recommended_action']);
        // AI-provided attack_type must be preserved when valid
        $this->assertSame('bot_attack', $result['attack_type']);
    }

    /**
     * P1-d: Bounded leniency - a highly confident SAFE verdict in the 50-79 band
     * softens the outcome only to SUSPICIOUS (step-up), never to a full SAFE.
     */
    public function test_confident_safe_gives_only_bounded_leniency(): void
    {
        $this->fakeAiResponse([
            'decision' => 'SAFE',
            'attack_type' => 'normal_user',
            'risk_score' => 55,
            'confidence' => 0.9,
            'recommended_action' => 'monitor',
        ]);

        $result = McpSecurityService::analyzeLogin([
            'ip' => '10.99.0.4',
            'email' => 'fusion4@test.com',
            'ip_attempts' => 5,    // 30
            'email_attempts' => 3, // 25 => 55
        ]);

        $this->assertSame('SUSPICIOUS', $result['decision']);
        $this->assertSame('F3_ai_bounded_leniency', $result['fusion']);
        $this->assertSame('otp', $result['recommended_action']); // step-up, not monitor
    }

    /**
     * P1-e: Low-confidence SAFE gets NO leniency (confidence >= 0.8 required).
     */
    public function test_low_confidence_safe_gets_no_leniency(): void
    {
        $this->fakeAiResponse([
            'decision' => 'SAFE',
            'risk_score' => 55,
            'confidence' => 0.5,
        ]);

        $result = McpSecurityService::analyzeLogin([
            'ip' => '10.99.0.5',
            'email' => 'fusion5@test.com',
            'ip_attempts' => 5,
            'email_attempts' => 3, // rule 55
        ]);

        $this->assertSame('SUSPICIOUS', $result['decision']);
        $this->assertSame('F4_rule_medium_score', $result['fusion']);
    }

    /**
     * P1-f: Invalid JSON -> AI marked unavailable, rules decide alone.
     */
    public function test_invalid_ai_response_falls_back_to_rules(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [['message' => ['content' => 'not json at all']]],
            ], 200),
        ]);

        $result = McpSecurityService::analyzeLogin([
            'ip' => '10.99.0.6',
            'email' => 'fusion6@test.com',
            'ip_attempts' => 5,
            'email_attempts' => 3, // rule 55
        ]);

        $this->assertFalse($result['ai_available']);
        $this->assertNull($result['ai_decision']);
        $this->assertSame('SUSPICIOUS', $result['decision']);
        $this->assertSame('rule_fallback', $result['source']);
    }

    /**
     * P1-g: Network failure -> AI unavailable, high rule score still blocks (guardrail intact).
     */
    public function test_ai_outage_keeps_high_score_guardrail(): void
    {
        Http::fake(function () {
            throw new \Exception('connection timeout');
        });

        $result = McpSecurityService::analyzeLogin([
            'ip' => '10.99.0.7',
            'email' => 'fusion7@test.com',
            'ip_attempts' => 10,
            'email_attempts' => 5, // rule 90
        ]);

        $this->assertFalse($result['ai_available']);
        $this->assertSame('MALICIOUS', $result['decision']);
        $this->assertSame(90, $result['risk_score']);
    }

    /**
     * P1-h: Pure unit checks of fuseDecision() without any HTTP layer.
     */
    public function test_fuse_decision_pure_matrix(): void
    {
        // F1
        $f1 = McpSecurityService::fuseDecision(null, 90);
        $this->assertSame(['MALICIOUS', 'F1_rule_guardrail_high_score'], [$f1['decision'], $f1['fusion']]);

        // F2: AI escalation in medium band
        $f2 = McpSecurityService::fuseDecision(['decision' => 'MALICIOUS', 'confidence' => 0.6, 'risk_score' => 50], 50);
        $this->assertSame('MALICIOUS', $f2['decision']);
        $this->assertSame('F2_ai_escalation', $f2['fusion']);

        // F3: bounded leniency
        $f3 = McpSecurityService::fuseDecision(['decision' => 'SAFE', 'confidence' => 0.85, 'risk_score' => 60], 60);
        $this->assertSame('SUSPICIOUS', $f3['decision']);

        // F4: rules alone in medium band
        $f4 = McpSecurityService::fuseDecision(null, 55);
        $this->assertSame('SUSPICIOUS', $f4['decision']);

        // F5: low score
        $f5 = McpSecurityService::fuseDecision(null, 0);
        $this->assertSame('SAFE', $f5['decision']);

        // Score floor: AI score 5 cannot pull rule 75 below 75
        $floor = McpSecurityService::fuseDecision(['decision' => 'SAFE', 'confidence' => 0.9, 'risk_score' => 5], 75);
        $this->assertSame(75, $floor['risk_score']);
        $this->assertSame('SUSPICIOUS', $floor['decision']); // F3 bounded leniency
    }

    /**
     * P1-i: parseAiResponse validation rules.
     */
    public function test_parse_ai_response_validation(): void
    {
        $this->assertNull(McpSecurityService::parseAiResponse(null));
        $this->assertNull(McpSecurityService::parseAiResponse(''));
        $this->assertNull(McpSecurityService::parseAiResponse('garbage'));
        $this->assertNull(McpSecurityService::parseAiResponse('{"decision":"BANANA"}'));
        $this->assertNull(McpSecurityService::parseAiResponse('{"risk_score":50}'));

        $ok = McpSecurityService::parseAiResponse('{"decision":"malicious","risk_score":150,"confidence":9}');
        $this->assertSame('MALICIOUS', $ok['decision']);
        $this->assertSame(100, $ok['risk_score']);  // clamped
        $this->assertSame(1.0, $ok['confidence']);  // clamped
    }
}
