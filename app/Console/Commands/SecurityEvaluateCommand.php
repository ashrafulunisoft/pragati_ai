<?php

namespace App\Console\Commands;

use App\Http\Middleware\McpMaliciousLoginMiddleware;
use App\Services\McpSecurityService;
use App\Services\SecurityStore;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Independent security evaluation harness (Problem 5 fix).
 *
 * Replaces the hand-designed "100-attempt simulation":
 *
 *  - Scenario GENERATORS model attacker/user BEHAVIOUR (attempt streams per
 *    IP/email). They are deliberately written against behavioural parameters
 *    (how many typos a human makes, how many IPs a stuffing campaign uses)
 *    and never read the detector's thresholds/weights - ground truth labels
 *    come from the generator, not from the scoring logic.
 *
 *  - The DETECTOR is the production pipeline itself: every simulated login
 *    attempt flows through the real McpMaliciousLoginMiddleware (and the real
 *    Redis counters), not a re-implementation.
 *
 *  - The AI layer runs in explicit modes (ablation):
 *      off         rules + degraded policy only (AI unavailable)
 *      honest      simulated competent AI (context heuristic, NOT ground truth)
 *      adversarial simulated fully-compromised AI (always SAFE - worst-case
 *                  prompt injection; measures the deterministic guardrails)
 *      live        the real MiniMax API (use sparingly for the paper run)
 *
 * Usage:
 *   php artisan security:evaluate --ai=off
 *   php artisan security:evaluate --ai=honest
 *   php artisan security:evaluate --ai=adversarial
 *   php artisan security:evaluate --ai=live --seed=7
 *   php artisan security:evaluate --ai=live --seeds=42,7,123,999,2026   # multi-run mean ± std (Comment 1)
 *
 * Reports: storage/app/security-evaluation/eval-<mode>-<timestamp>.{json,md}
 */
class SecurityEvaluateCommand extends Command
{
    protected $signature = 'security:evaluate
                            {--ai=off : AI mode: off|honest|adversarial|live}
                            {--scenario=all : scenario key or "all"}
                            {--seed=42 : RNG seed for reproducibility}
                            {--seeds= : comma-separated seeds, e.g. 42,7,123,999,2026 (multi-run mean ± std — Comment 1)}
                            {--legit-users=20 : legitimate users in the legitimate scenario}
                            {--engine=hysa : Baseline engine: hysa|laravel_throttle|redis_only|rule_based (Comment 6)}
                            {--driver= : Override MCP driver: direct|mcp (MCP ablation — Comment 6)}';

    protected $description = 'Run the independent login-security evaluation harness (confusion matrix + metrics per attack scenario)';

    private array $results = [];
    private int $alertsFired = 0;

    public function handle(): int
    {
        $engine = (string) ($this->option('engine') ?? 'hysa');
        if (!in_array($engine, ['hysa', 'laravel_throttle', 'redis_only', 'rule_based'], true)) {
            $this->error("Unknown --engine '{$engine}' (hysa|laravel_throttle|redis_only|rule_based)");

            return 1;
        }

        $driver = (string) $this->option('driver');
        if ($driver !== '') {
            if (!in_array($driver, ['direct', 'mcp'], true)) {
                $this->error("Unknown --driver '{$driver}' (direct|mcp)");

                return 1;
            }
            config(['services.minimax.driver' => $driver]);
        }

        $mode = (string) $this->option('ai');
        if ($engine === 'rule_based' || $engine === 'laravel_throttle' || $engine === 'redis_only') {
            $mode = 'off';
        }

        if (!in_array($mode, ['off', 'honest', 'adversarial', 'live'], true)) {
            $this->error("Unknown --ai mode '{$mode}' (off|honest|adversarial|live)");

            return 1;
        }

        /*
         * Evaluation must never send real alert emails: replace the email
         * listener with a counter so "alerts fired" is still measured.
         */
        \Illuminate\Support\Facades\Event::forget(\App\Events\McpAttackDetected::class);
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\McpAttackDetected::class,
            fn () => $this->alertsFired++
        );

        /*
         * Multi-seed support (journal reviewer Comment 1): pass
         * --seeds=42,7,123,999,2026 to run the whole evaluation once per seed
         * and aggregate mean ± standard deviation across runs (variation
         * reporting the reviewer asked for). Single-seed behaviour is
         * unchanged when only --seed is given.
         */
        $seeds = array_values(array_filter(array_map(
            fn ($s) => (int) trim($s),
            explode(',', (string) $this->option('seeds'))
        )));
        if ($seeds === []) {
            $seeds = [(int) $this->option('seed')];
        }

        $reports = [];
        foreach ($seeds as $seed) {
            // Per-run reset: results, alert counter and live AI observability
            $this->results = [];
            $this->alertsFired = 0;
            McpSecurityService::$liveStats = ['ai_calls' => 0, 'ai_invalid' => 0, 'ai_errors' => 0];

            mt_srand($seed);
            $this->configureAi($mode);

            $scenarios = $this->scenarios((int) $this->option('legit-users'));
            $only = (string) $this->option('scenario');

            /*
             * --scenario accepts "all", a single key, or a comma-separated
             * subset (e.g. the paper's original 7-scenario trace:
             * "legitimate,brute_force,credential_stuffing,password_spraying,
             *  distributed_stuffing,low_and_slow,nat_shared"). Selecting a
             * subset keeps ONE continuous RNG stream across the selected
             * scenarios, exactly like a single full run.
             */
            $selected = $only === 'all'
                ? null
                : array_map('trim', explode(',', $only));

            foreach ($scenarios as $key => $builder) {
                if ($selected !== null && !in_array($key, $selected, true)) {
                    continue;
                }
                $this->info("Running scenario: {$key} (ai={$mode}, seed={$seed})");
                $this->results[$key] = $this->runScenario($key, $builder);
            }

            $reports[] = $this->buildReport($mode, $seed);
        }

        if (count($reports) === 1) {
            $this->writeReport($mode, $reports[0]);
            $this->renderTable($reports[0]);

            return 0;
        }

        $aggregate = $this->aggregate($reports);
        $this->writeMultiReport($mode, $reports, $aggregate);
        $this->renderAggregateTable($reports, $aggregate);

        return 0;
    }

    // ------------------------------------------------------------------
    // AI modes
    // ------------------------------------------------------------------

    private function configureAi(string $mode): void
    {
        match ($mode) {
            'off' => Http::fake(fn () => throw new \Exception('ai unavailable (mode: off)')),
            'honest' => Http::fake(function ($request) {
                // A competent analyst reading ONLY the prompt context.
                // Heuristic on the context fields - never touches ground truth.
                $prompt = $request->data()['messages'][1]['content'] ?? '';
                $ip = (int) $this->promptField($prompt, 'ip_attempts');
                $email = (int) $this->promptField($prompt, 'email_attempts');
                $malicious = $ip >= 4 || $email >= 2;
                return Http::response(['choices' => [['message' => ['content' => json_encode([
                    'decision' => $malicious ? 'MALICIOUS' : 'SAFE',
                    'attack_type' => $malicious ? 'brute_force' : 'normal_user',
                    'risk_score' => $malicious ? max(50, $ip * 5 + $email * 8) : 5,
                    'confidence' => 0.85,
                    'recommended_action' => $malicious ? 'block_ip' : 'monitor',
                ])]]]], 200);
            }),
            'adversarial' => Http::fake(fn () => Http::response(['choices' => [['message' => ['content' => json_encode([
                'decision' => 'SAFE', 'attack_type' => 'normal_user',
                'risk_score' => 0, 'confidence' => 1.0, 'recommended_action' => 'monitor',
            ])]]]], 200)),
            'live' => null, // real MiniMax API via configured key
            default => null,
        };
    }

    private function promptField(string $prompt, string $field): int
    {
        preg_match('/'.$field.':\s*(\d+)/', $prompt, $m);

        return (int) ($m[1] ?? 0);
    }

    // ------------------------------------------------------------------
    // Scenario generators (behaviour models - independent of the detector)
    // ------------------------------------------------------------------

    private function scenarios(int $legitUsers): array
    {
        return [
            /*
             * Legitimate users: each from their own IP, 0-2 typo failures,
             * then a successful login. (Behaviour: humans typo a couple of
             * times before succeeding.)
             */
            'legitimate' => fn () => $this->genLegitimate($legitUsers),

            /*
             * Brute force: one IP hammering one account with 15-25 guesses.
             */
            'brute_force' => fn () => $this->genBruteForce(),

            /*
             * Credential stuffing: ONE victim account hit from 10-14 fresh
             * IPs, 1-2 guesses each (per-IP volume stays low).
             */
            'credential_stuffing' => fn () => $this->genCredentialStuffing(),

            /*
             * Password spraying: one IP tries a common password against
             * 18-22 different accounts (per-account volume stays low).
             */
            'password_spraying' => fn () => $this->genPasswordSpraying(),

            /*
             * Distributed stuffing: 30 bots x 2 accounts each, 1 guess per
             * pair - both per-IP and per-email counters stay tiny.
             */
            'distributed_stuffing' => fn () => $this->genDistributed(),

            /*
             * Below-threshold joint rotation (Reviewer Comment 2):
             * Attacker jointly rotates source IPs (<=4 attempts each) and
             * accounts (<=2 attempts each), deliberately staying below
             * theta_ip (5) and theta_email (3) to test the escalation gap.
             */
            'below_threshold_rotation' => fn () => $this->genBelowThresholdRotation(),

            /*
             * Low-and-slow: a patient attacker making only 2 guesses this
             * window (documents the accepted blind spot of window counters).
             */
            'low_and_slow' => fn () => $this->genLowAndSlow(),

            /*
             * Shared NAT: 6 legitimate users behind one office IP plus one
             * attacker from the same IP (collateral-damage measurement).
             */
            'nat_shared' => fn () => $this->genNatShared(),

            /*
             * NAT Gaming (Reviewer Comment 8):
             * Attacker controls 3 valid accounts and deliberately authenticates
             * them successfully from one IP to game the shared-NAT threshold.
             * Then, attacker rotates victim accounts (10 victim accounts x 3 guesses = 30 attempts)
             * to test evasion of source-level blocking.
             */
            'nat_gaming' => fn () => $this->genNatGaming(),
        ];
    }

    /**
     * Every event: [ip, email, is_attack, is_success]
     */
    private function genLegitimate(int $users): array
    {
        $events = [];
        for ($u = 0; $u < $users; $u++) {
            $ip = "172.16.{$u}.10";
            $email = "legit{$u}@corp.com";
            $typos = mt_rand(0, 2);
            for ($t = 0; $t < $typos; $t++) {
                $events[] = [$ip, $email, false, false];
            }
            $events[] = [$ip, $email, false, true];
        }

        return $events;
    }

    private function genBruteForce(): array
    {
        $events = [];
        $guesses = mt_rand(15, 25);
        for ($g = 0; $g < $guesses; $g++) {
            $events[] = ['203.0.113.50', 'victim@corp.com', true, false];
        }

        return $events;
    }

    private function genCredentialStuffing(): array
    {
        $events = [];
        $bots = mt_rand(10, 14);
        for ($b = 0; $b < $bots; $b++) {
            $guesses = mt_rand(1, 2);
            for ($g = 0; $g < $guesses; $g++) {
                $events[] = ["198.51.100.{$b}", 'victim@corp.com', true, false];
            }
        }

        return $events;
    }

    private function genPasswordSpraying(): array
    {
        $events = [];
        $accounts = mt_rand(18, 22);
        for ($a = 0; $a < $accounts; $a++) {
            $events[] = ['203.0.113.90', "employee{$a}@corp.com", true, false];
        }

        return $events;
    }

    private function genDistributed(): array
    {
        $events = [];
        for ($b = 0; $b < 30; $b++) {
            for ($a = 0; $a < 2; $a++) {
                $events[] = ["192.0.2.{$b}", "target{$a}@corp.com", true, false];
            }
        }

        return $events;
    }

    /**
     * Below-threshold joint rotation (Reviewer Comment 2):
     * 20 IPs x 2 attempts each = 40 attempts across 20 distinct accounts.
     * Each IP tests exactly 2 accounts (2 < 5).
     * Each account receives exactly 2 attempts (2 < 3).
     * Demonstrates that neither threshold is crossed -> 0 AI invocations -> 100% non-escalation.
     */
    private function genBelowThresholdRotation(): array
    {
        $events = [];
        $count = 20;
        for ($i = 0; $i < $count; $i++) {
            $ip = "198.51.100." . (100 + $i + 1);
            $account1 = "belowrot{$i}@corp.com";
            $account2 = "belowrot" . (($i + 1) % $count) . "@corp.com";
            $events[] = [$ip, $account1, true, false];
            $events[] = [$ip, $account2, true, false];
        }

        return $events;
    }

    private function genLowAndSlow(): array
    {
        return [
            ['203.0.113.120', 'patient@corp.com', true, false],
            ['203.0.113.120', 'patient@corp.com', true, false],
        ];
    }

    private function genNatShared(): array
    {
        $events = [];
        // 6 legitimate users behind the office NAT, 0-1 typo each, then success
        for ($u = 0; $u < 6; $u++) {
            $email = "office{$u}@corp.com";
            if (mt_rand(0, 1) === 1) {
                $events[] = ['100.64.0.1', $email, false, false];
            }
            $events[] = ['100.64.0.1', $email, false, true];
        }
        // one attacker from the same shared IP
        for ($g = 0; $g < 8; $g++) {
            $events[] = ['100.64.0.1', 'victim@corp.com', true, false];
        }

        return $events;
    }

    private function genNatGaming(): array
    {
        $events = [];
        $ip = '100.64.99.1';

        // Phase 1 (Seeding): Attacker logs in with 3 distinct valid accounts from this IP
        // to game the NAT condition (SCARD >= 3)
        for ($s = 0; $s < 3; $s++) {
            $events[] = [$ip, "attacker_valid_{$s}@test.com", false, true];
        }

        // Phase 2 (Attack): Attacker attacks 10 victim accounts from the same IP,
        // making 3 failed guesses per victim account (30 attempts).
        for ($v = 0; $v < 10; $v++) {
            for ($g = 0; $g < 3; $g++) {
                $events[] = [$ip, "victim_{$v}@target.com", true, false];
            }
        }

        return $events;
    }

    // ------------------------------------------------------------------
    // Detector execution (the production pipeline, nothing re-implemented)
    // ------------------------------------------------------------------

    private function runScenario(string $key, callable $builder): array
    {
        $events = $builder();
        $middleware = new McpMaliciousLoginMiddleware();

        $tp = $fp = $tn = $fn = 0;
        $aiInvocations = 0;
        $attackAiInvocations = 0;
        $legitChallenged = 0;
        $challengeDenials = 0;
        $usedKeys = [];

        // Pre-clean scenario keys to guarantee zero cross-scenario or stale pollution
        foreach ($events as [$ip, $email, $isAttack, $isSuccess]) {
            $usedKeys["attack:login:ip:$ip"] = true;
            $usedKeys["attack:login:email:$email"] = true;
        }
        SecurityStore::del(array_keys($usedKeys));
        foreach (array_keys($usedKeys) as $k) {
            if (str_starts_with($k, 'attack:login:ip:')) {
                $src = substr($k, strlen('attack:login:ip:'));
                SecurityStore::del(['blocked:ip:'.$src, 'mcp:degraded:ip:'.$src, 'challenge:issued:ip:'.$src, 'success:emails:ip:'.$src, 'blocked:accounts:ip:'.$src]);
            } elseif (str_starts_with($k, 'attack:login:email:')) {
                $src = substr($k, strlen('attack:login:email:'));
                SecurityStore::del(['blocked:email:'.$src]);
            }
        }

        $attackBlocked = 0;
        $attackThrottled = 0;
        $attackChallenged = 0;
        $attackAllowed = 0;
        $attackClassifiedMalicious = 0;
        $attackClassifiedSuspicious = 0;
        $attackClassifiedSafe = 0;

        $legitBlocked = 0;
        $legitChallenged = 0;
        $legitAllowed = 0;
        $legitClassifiedMalicious = 0;
        $legitClassifiedSuspicious = 0;
        $legitClassifiedSafe = 0;

        $engine = (string) ($this->option('engine') ?? 'hysa');
        $latencies = [];
        $usedThrottleKeys = [];

        foreach ($events as [$ip, $email, $isAttack, $isSuccess]) {
            $ipKey = "attack:login:ip:$ip";
            $emailKey = "attack:login:email:$email";
            $usedKeys[$ipKey] = true;
            $usedKeys[$emailKey] = true;

            $startReq = microtime(true);

            if ($engine === 'laravel_throttle') {
                $throttleKey = strtolower($email).'|'.$ip;
                $usedThrottleKeys[$throttleKey] = true;
                $maxAttempts = 5;
                if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
                    $status = 429;
                    $decision = 'MALICIOUS';
                    $enforcement = 'THROTTLED';
                } else {
                    $status = 200;
                    $decision = 'SAFE';
                    $enforcement = 'ALLOWED';
                }
            } elseif ($engine === 'redis_only') {
                $alreadyBlocked = SecurityStore::get("blocked:ip:$ip") || SecurityStore::get("blocked:email:$email");
                if ($alreadyBlocked) {
                    $status = 403;
                    $decision = 'MALICIOUS';
                    $enforcement = 'BLOCKED';
                } else {
                    $ipAtt = (int) SecurityStore::get($ipKey);
                    $emailAtt = (int) SecurityStore::get($emailKey);
                    if ($ipAtt >= 5 || $emailAtt >= 3) {
                        SecurityStore::setex("blocked:ip:$ip", 3600, 1);
                        $status = 403;
                        $decision = 'MALICIOUS';
                        $enforcement = 'BLOCKED';
                    } else {
                        $status = 200;
                        $decision = 'SAFE';
                        $enforcement = 'ALLOWED';
                    }
                }
            } else {
                // hysa or rule_based
                $ipAtt = (int) SecurityStore::get($ipKey);
                $emailAtt = (int) SecurityStore::get($emailKey);
                $alreadyBlocked = SecurityStore::get("blocked:ip:$ip") || SecurityStore::get("blocked:email:$email");
                if (!$alreadyBlocked && ($ipAtt >= 5 || $emailAtt >= 3)) {
                    $aiInvocations++;
                    if ($isAttack) {
                        $attackAiInvocations++;
                    }
                }

                $request = Request::create('/login', 'POST', [
                    'email' => $email, 'password' => 'guess',
                ], [], [], ['REMOTE_ADDR' => $ip]);

                $response = $middleware->handle($request, fn () => response('ALLOWED'));
                $status = $response->getStatusCode();

                // Comment 5: Extract both classification decision and enforcement status
                $decision = $response->headers->get('X-Mcp-Decision')
                    ?? ($status === 403 ? 'MALICIOUS' : ($status === 428 ? 'SUSPICIOUS' : 'SAFE'));
            }

            $reqLatencyMs = (microtime(true) - $startReq) * 1000;
            $latencies[] = $reqLatencyMs;

            if ($isAttack) {
                if ($decision === 'MALICIOUS') {
                    $attackClassifiedMalicious++;
                } elseif ($decision === 'SUSPICIOUS') {
                    $attackClassifiedSuspicious++;
                } else {
                    $attackClassifiedSafe++;
                }

                if ($status === 403) {
                    $attackBlocked++;
                } elseif ($status === 429) {
                    $attackThrottled++;
                } elseif ($status === 428) {
                    $attackChallenged++;
                } elseif ($status === 200) {
                    $attackAllowed++;
                }
            } else {
                if ($decision === 'MALICIOUS') {
                    $legitClassifiedMalicious++;
                } elseif ($decision === 'SUSPICIOUS') {
                    $legitClassifiedSuspicious++;
                } else {
                    $legitClassifiedSafe++;
                }

                if ($status === 403) {
                    $legitBlocked++;
                } elseif ($status === 428) {
                    $legitChallenged++;
                } elseif ($status === 200) {
                    $legitAllowed++;
                }
            }

            // The harness never solves challenges: a 428 step-up denial stops
            // scripted traffic exactly like a block (attackers cannot pass).
            $challenged = $status === 428;
            $predictedAttack = in_array($status, [403, 429, 428], true);

            if ($challenged) {
                $challengeDenials++;
            }

            // Tally against generator ground truth
            if ($isAttack && $predictedAttack) {
                $tp++;
            } elseif ($isAttack && !$predictedAttack) {
                $fn++;
            } elseif (!$isAttack && $predictedAttack) {
                $fp++;
            } else {
                $tn++;
            }

            // State lifecycle mutation across engines
            if ($engine === 'laravel_throttle') {
                $throttleKey = strtolower($email).'|'.$ip;
                if ($status === 200 && $isSuccess) {
                    \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);
                } elseif ($status === 200) {
                    \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 900);
                }
            } elseif ($engine === 'redis_only') {
                if ($status === 200 && $isSuccess) {
                    SecurityStore::del([$ipKey, $emailKey, "blocked:ip:$ip", "blocked:email:$email"]);
                } elseif ($status === 200) {
                    SecurityStore::incrWithTtl($ipKey, 900);
                    SecurityStore::incrWithTtl($emailKey, 900);
                }
            } else {
                // Controller behaviour: failure increments, success resets
                if ($status === 200 && $isSuccess) {
                    SecurityStore::sAddWithTtl("success:emails:ip:$ip", $email, 900);
                    SecurityStore::del([$emailKey]);
                    $maxBlocked = (int) config('services.minimax.shared_ip_max_blocked_accounts', 5);
                    if (SecurityStore::scard("blocked:accounts:ip:$ip") < $maxBlocked) {
                        SecurityStore::del([$ipKey, "blocked:ip:$ip"]);
                    }
                } elseif ($status === 200) {
                    SecurityStore::incrWithTtl($ipKey, 900);
                    SecurityStore::incrWithTtl($emailKey, 900);
                }
            }
            // denied requests do not increment (attacker never reaches controller)
        }

        // cleanup scenario state
        SecurityStore::del(array_keys($usedKeys));
        foreach (array_keys($usedKeys) as $k) {
            if (str_starts_with($k, 'attack:login:ip:')) {
                $src = substr($k, strlen('attack:login:ip:'));
                SecurityStore::del(['blocked:ip:'.$src, 'mcp:degraded:ip:'.$src, 'challenge:issued:ip:'.$src, 'success:emails:ip:'.$src, 'blocked:accounts:ip:'.$src]);
            }
        }
        foreach (array_keys($usedKeys) as $k) {
            if (str_starts_with($k, 'attack:login:email:')) {
                SecurityStore::del(['blocked:email:'.substr($k, strlen('attack:login:email:'))]);
            }
        }
        foreach (array_keys($usedThrottleKeys) as $tk) {
            \Illuminate\Support\Facades\RateLimiter::clear($tk);
        }

        sort($latencies);
        $nLat = count($latencies);
        $meanLat = $nLat > 0 ? round(array_sum($latencies) / $nLat, 2) : 0.0;
        $p50Lat = $nLat > 0 ? round($latencies[(int) ($nLat * 0.50)], 2) : 0.0;
        $p95Lat = $nLat > 0 ? round($latencies[(int) min($nLat - 1, (int) ($nLat * 0.95))], 2) : 0.0;
        $p99Lat = $nLat > 0 ? round($latencies[(int) min($nLat - 1, (int) ($nLat * 0.99))], 2) : 0.0;
        $totalTimeSec = array_sum($latencies) / 1000;
        $throughputRps = $totalTimeSec > 0 ? round($nLat / $totalTimeSec, 1) : 0.0;

        $latency = [
            'mean_ms' => $meanLat,
            'p50_ms'  => $p50Lat,
            'p95_ms'  => $p95Lat,
            'p99_ms'  => $p99Lat,
            'throughput_rps' => $throughputRps,
        ];

        $classification = [
            'attack_malicious'  => $attackClassifiedMalicious,
            'attack_suspicious' => $attackClassifiedSuspicious,
            'attack_safe'       => $attackClassifiedSafe,
            'legit_malicious'   => $legitClassifiedMalicious,
            'legit_suspicious'  => $legitClassifiedSuspicious,
            'legit_safe'        => $legitClassifiedSafe,
        ];
        $enforcement = [
            'attack_blocked'    => $attackBlocked,
            'attack_throttled'  => $attackThrottled,
            'attack_challenged' => $attackChallenged,
            'attack_allowed'    => $attackAllowed,
            'legit_blocked'     => $legitBlocked,
            'legit_challenged'  => $legitChallenged,
            'legit_allowed'     => $legitAllowed,
        ];

        return $this->metrics(
            $tp, $fp, $tn, $fn, $aiInvocations, count($events),
            $challengeDenials, $legitChallenged, $attackAiInvocations,
            $classification, $enforcement, $latency
        );
    }

    private function metrics(
        int $tp, int $fp, int $tn, int $fn, int $aiCalls, int $total,
        int $challengeDenials = 0, int $legitChallenged = 0, int $attackAiCalls = 0,
        array $classification = [], array $enforcement = [], array $latency = []
    ): array {
        $positive = $tp + $fn;
        $negative = $tn + $fp;
        $aiNeverInvokedFraction = $positive > 0 ? round(max(0, $positive - $attackAiCalls) / $positive, 4) : null;

        // 95% Confidence Interval for False Positive Rate (Comment 5)
        $fprCi95RuleOfThree = $negative > 0 ? round(3 / $negative, 4) : 0.0;
        $z = 1.96;
        $z2 = $z * $z;
        $denom = $negative > 0 ? (1 + $z2 / $negative) : 1;
        $p = $negative > 0 ? ($fp / $negative) : 0;
        $center = $negative > 0 ? ($p + $z2 / (2 * $negative)) / $denom : 0;
        $margin = $negative > 0 ? ($z * sqrt(($p * (1 - $p) + $z2 / (4 * $negative)) / $negative)) / $denom : 0;
        $fprCi95Wilson = [
            'lower' => max(0.0, round($center - $margin, 4)),
            'upper' => min(1.0, round($center + $margin, 4)),
        ];

        return [
            'events' => $total,
            'attacks' => $positive,
            'legitimate' => $negative,
            'tp' => $tp, 'fp' => $fp, 'tn' => $tn, 'fn' => $fn,
            'accuracy' => $total > 0 ? round(($tp + $tn) / $total, 4) : null,
            'precision' => ($tp + $fp) > 0 ? round($tp / ($tp + $fp), 4) : null,
            'recall' => $positive > 0 ? round($tp / $positive, 4) : null,
            'specificity' => $negative > 0 ? round($tn / $negative, 4) : null,
            'fpr' => $negative > 0 ? round($fp / $negative, 4) : null,
            'fnr' => $positive > 0 ? round($fn / $positive, 4) : null,
            'f1' => ($tp + $fp + $fn) > 0 ? round(2 * $tp / (2 * $tp + $fp + $fn), 4) : null,
            'challenge_denials' => $challengeDenials,
            'legit_challenged' => $legitChallenged,
            'ai_invocations' => $aiCalls,
            'attack_ai_invocations' => $attackAiCalls,
            'ai_invocation_rate' => $total > 0 ? round($aiCalls / $total, 4) : null,
            'ai_never_invoked_fraction' => $aiNeverInvokedFraction,
            'classification' => $classification,
            'enforcement' => $enforcement,
            'fpr_ci95' => [
                'wilson_lower' => $fprCi95Wilson['lower'],
                'wilson_upper' => $fprCi95Wilson['upper'],
                'rule_of_three_upper' => $fprCi95RuleOfThree,
            ],
            'latency' => $latency,
        ];
    }

    // ------------------------------------------------------------------
    // Reporting
    // ------------------------------------------------------------------

    private function buildReport(string $mode, int $seed): array
    {
        $classSums = [
            'attack_malicious'  => array_sum(array_column(array_column($this->results, 'classification'), 'attack_malicious')),
            'attack_suspicious' => array_sum(array_column(array_column($this->results, 'classification'), 'attack_suspicious')),
            'attack_safe'       => array_sum(array_column(array_column($this->results, 'classification'), 'attack_safe')),
            'legit_malicious'   => array_sum(array_column(array_column($this->results, 'classification'), 'legit_malicious')),
            'legit_suspicious'  => array_sum(array_column(array_column($this->results, 'classification'), 'legit_suspicious')),
            'legit_safe'        => array_sum(array_column(array_column($this->results, 'classification'), 'legit_safe')),
        ];
        $enforceSums = [
            'attack_blocked'    => array_sum(array_column(array_column($this->results, 'enforcement'), 'attack_blocked')),
            'attack_throttled'  => array_sum(array_column(array_column($this->results, 'enforcement'), 'attack_throttled')),
            'attack_challenged' => array_sum(array_column(array_column($this->results, 'enforcement'), 'attack_challenged')),
            'attack_allowed'    => array_sum(array_column(array_column($this->results, 'enforcement'), 'attack_allowed')),
            'legit_blocked'     => array_sum(array_column(array_column($this->results, 'enforcement'), 'legit_blocked')),
            'legit_challenged'  => array_sum(array_column(array_column($this->results, 'enforcement'), 'legit_challenged')),
            'legit_allowed'     => array_sum(array_column(array_column($this->results, 'enforcement'), 'legit_allowed')),
        ];

        $latMeans = array_values(array_filter(array_column(array_column($this->results, 'latency'), 'mean_ms')));
        $totLat = [
            'mean_ms' => count($latMeans) > 0 ? round(array_sum($latMeans) / count($latMeans), 2) : 0.0,
            'p50_ms'  => count($latMeans) > 0 ? round(array_sum(array_column(array_column($this->results, 'latency'), 'p50_ms')) / count($latMeans), 2) : 0.0,
            'p95_ms'  => count($latMeans) > 0 ? round(array_sum(array_column(array_column($this->results, 'latency'), 'p95_ms')) / count($latMeans), 2) : 0.0,
            'p99_ms'  => count($latMeans) > 0 ? round(array_sum(array_column(array_column($this->results, 'latency'), 'p99_ms')) / count($latMeans), 2) : 0.0,
            'throughput_rps' => count($latMeans) > 0 ? round(array_sum(array_column(array_column($this->results, 'latency'), 'throughput_rps')) / count($latMeans), 1) : 0.0,
        ];

        $totals = $this->metrics(
            array_sum(array_column($this->results, 'tp')),
            array_sum(array_column($this->results, 'fp')),
            array_sum(array_column($this->results, 'tn')),
            array_sum(array_column($this->results, 'fn')),
            array_sum(array_column($this->results, 'ai_invocations')),
            array_sum(array_column($this->results, 'events')),
            array_sum(array_column($this->results, 'challenge_denials')),
            array_sum(array_column($this->results, 'legit_challenged')),
            array_sum(array_column($this->results, 'attack_ai_invocations')),
            $classSums,
            $enforceSums,
            $totLat
        );

        return [
            'generated_at' => now()->toIso8601String(),
            'ai_mode' => $mode,
            'engine'  => (string) ($this->option('engine') ?? 'hysa'),
            'seed' => $seed,
            // Model transparency (Comment 1): every report states exactly
            // which provider/model/host produced the AI verdicts.
            'model' => [
                'provider' => config('services.minimax.provider'),
                'model'    => config('services.minimax.model'),
                'host'     => config('services.minimax.host'),
                'driver'   => config('services.minimax.driver'),
            ],
            'ai_stats' => McpSecurityService::$liveStats,
            'scenarios' => $this->results,
            'overall' => $totals,
            'alerts_fired' => $this->alertsFired,
        ];
    }

    /**
     * Mean ± sample standard deviation of the overall metrics across seeds
     * (journal reviewer Comment 1: "report variation across repeated runs").
     */
    private function aggregate(array $reports): array
    {
        $out = ['runs' => count($reports)];

        foreach (['accuracy', 'precision', 'recall', 'specificity', 'fpr', 'fnr', 'f1'] as $key) {
            $vals = array_values(array_filter(
                array_column(array_column($reports, 'overall'), $key),
                fn ($v) => $v !== null
            ));
            $n = count($vals);
            $mean = $n > 0 ? round(array_sum($vals) / $n, 4) : null;
            $std = 0.0;
            if ($n > 1) {
                $var = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $vals)) / ($n - 1);
                $std = round(sqrt($var), 4);
            }
            $out[$key] = ['mean' => $mean, 'std' => $std, 'n' => $n];
        }

        foreach (['events', 'attacks', 'legitimate', 'tp', 'fp', 'tn', 'fn', 'challenge_denials', 'legit_challenged', 'ai_invocations'] as $key) {
            $out['sum_'.$key] = array_sum(array_column(array_column($reports, 'overall'), $key));
        }

        return $out;
    }

    private function writeReport(string $mode, array $report): void
    {
        $dir = storage_path('app/security-evaluation');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $stamp = now()->format('Ymd_His');
        file_put_contents("{$dir}/eval-{$mode}-{$stamp}.json", json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $md = "# Security evaluation - ai={$mode}\n\n";
        $md .= "Generated: {$report['generated_at']} | seed: {$report['seed']}\n";
        $m = $report['model'] ?? null;
        if ($m) {
            $md .= "Model: {$m['provider']} / {$m['model']} (driver: {$m['driver']})\n";
            $md .= "Host: {$m['host']}\n";
        }
        $s = $report['ai_stats'] ?? [];
        if ($s) {
            $md .= "AI transport: calls={$s['ai_calls']}, unparseable={$s['ai_invalid']}, errors={$s['ai_errors']}\n";
        }
        $md .= "\n";
        $md .= "| Scenario | Events | TP | FP | TN | FN | Accuracy | Precision | Recall | FPR | F1 | Challenged | LegitChallenged | AI calls | AI Never Invoked % |\n";
        $md .= "|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|\n";
        foreach ($report['scenarios'] as $key => $m) {
            $md .= sprintf(
                "| %s | %d | %d | %d | %d | %d | %s | %s | %s | %s | %s | %d | %d | %d | %s |\n",
                $key, $m['events'], $m['tp'], $m['fp'], $m['tn'], $m['fn'],
                $m['accuracy'] ?? '-', $m['precision'] ?? '-', $m['recall'] ?? '-',
                $m['fpr'] ?? '-', $m['f1'] ?? '-', $m['challenge_denials'], $m['legit_challenged'], $m['ai_invocations'],
                $m['ai_never_invoked_fraction'] !== null ? number_format($m['ai_never_invoked_fraction'] * 100, 1).'%' : '-'
            );
        }
        $o = $report['overall'];
        $md .= sprintf(
            "| **OVERALL** | %d | %d | %d | %d | %d | %s | %s | %s | %s | %s | %d | %d | %d | %s |\n",
            $o['events'], $o['tp'], $o['fp'], $o['tn'], $o['fn'],
            $o['accuracy'], $o['precision'], $o['recall'], $o['fpr'], $o['f1'],
            $o['challenge_denials'], $o['legit_challenged'], $o['ai_invocations'],
            $o['ai_never_invoked_fraction'] !== null ? number_format($o['ai_never_invoked_fraction'] * 100, 1).'%' : '-'
        );
        $md .= "\nAI invocation rate: ".($o['ai_invocation_rate'] ?? '-')."\n";

        $cls = $o['classification'] ?? [];
        $enf = $o['enforcement'] ?? [];
        $ci  = $o['fpr_ci95'] ?? [];

        $md .= "\n### Threat Detection vs. Challenge-Based Prevention Separation (Reviewer Comment 5)\n\n";
        $md .= "#### 1. Threat Classification (Decision Layer)\n";
        $md .= "- **Attack Events ({$o['attacks']}):**\n";
        $md .= "  - Classified MALICIOUS: " . ($cls['attack_malicious'] ?? 0) . "\n";
        $md .= "  - Classified SUSPICIOUS: " . ($cls['attack_suspicious'] ?? 0) . "\n";
        $md .= "  - Classified SAFE (Missed): " . ($cls['attack_safe'] ?? 0) . "\n";
        $md .= "- **Legitimate Events ({$o['legitimate']}):**\n";
        $md .= "  - Classified SAFE: " . ($cls['legit_safe'] ?? 0) . "\n";
        $md .= "  - Classified SUSPICIOUS (Friction): " . ($cls['legit_suspicious'] ?? 0) . "\n";
        $md .= "  - Classified MALICIOUS (Hard FP): " . ($cls['legit_malicious'] ?? 0) . "\n\n";

        $md .= "#### 2. Downstream Enforcement (Action Layer)\n";
        $md .= "- **Attack Prevention Breakdown:**\n";
        $md .= "  - Explicit Hard Block (HTTP 403): " . ($enf['attack_blocked'] ?? 0) . "\n";
        $md .= "  - Heightened Rate Throttle (HTTP 429): " . ($enf['attack_throttled'] ?? 0) . "\n";
        $md .= "  - Step-Up Challenge Unsolved (HTTP 428): " . ($enf['attack_challenged'] ?? 0) . "\n";
        $md .= "  - Unauthorized Allowed-Through (HTTP 200 FN): " . ($enf['attack_allowed'] ?? 0) . "\n";
        $md .= "- **Legitimate User Impact:**\n";
        $md .= "  - Clean Allow (HTTP 200): " . ($enf['legit_allowed'] ?? 0) . "\n";
        $md .= "  - Step-Up Verification Friction (HTTP 428): " . ($enf['legit_challenged'] ?? 0) . "\n";
        $md .= "  - Collateral Hard Block (HTTP 403): " . ($enf['legit_blocked'] ?? 0) . "\n\n";

        $md .= "#### 3. False Positive Rate Uncertainty (Sample Size n={$o['legitimate']})\n";
        $md .= "- Observed FPR: " . ($o['fpr'] ?? '0.00%') . " ({$o['fp']}/{$o['legitimate']})\n";
        $md .= "- 95% Wilson Score Interval: [" . ($ci['wilson_lower'] ?? 0) . ", " . ($ci['wilson_upper'] ?? 0) . "]\n";
        $md .= "- 95% Rule of Three Upper Bound: " . (isset($ci['rule_of_three_upper']) ? number_format($ci['rule_of_three_upper'] * 100, 2) . '%' : '-') . "\n\n";

        $lat = $o['latency'] ?? [];
        $md .= "### Controlled Baseline & Performance Statistics (Reviewer Comment 6)\n";
        $md .= "- **Engine:** " . ($report['engine'] ?? 'hysa') . "\n";
        $md .= "- **Driver:** " . ($report['model']['driver'] ?? 'direct') . "\n";
        $md .= "- **Mean Request Latency:** " . ($lat['mean_ms'] ?? 0) . " ms\n";
        $md .= "- **p50 Latency:** " . ($lat['p50_ms'] ?? 0) . " ms\n";
        $md .= "- **p95 Latency:** " . ($lat['p95_ms'] ?? 0) . " ms\n";
        $md .= "- **p99 Latency:** " . ($lat['p99_ms'] ?? 0) . " ms\n";
        $md .= "- **Throughput:** " . ($lat['throughput_rps'] ?? 0) . " req/s\n";

        file_put_contents("{$dir}/eval-{$mode}-{$stamp}.md", $md);
        $this->line("Report written: {$dir}/eval-{$mode}-{$stamp}.md");
    }

    /**
     * Multi-seed report (Comment 1): per-seed overall rows plus a
     * mean ± std aggregate — the "variation across repeated runs" table the
     * reviewer asked for, with the model/host stated for transparency.
     */
    private function writeMultiReport(string $mode, array $reports, array $aggregate): void
    {
        $dir = storage_path('app/security-evaluation');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $stamp = now()->format('Ymd_His');
        file_put_contents(
            "{$dir}/eval-{$mode}-multi-{$stamp}.json",
            json_encode(['aggregate' => $aggregate, 'runs' => $reports], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $m = $reports[0]['model'] ?? null;
        $md = "# Security evaluation (multi-seed) - ai={$mode}\n\n";
        $md .= "Generated: {$reports[0]['generated_at']} | seeds: ".implode(', ', array_column($reports, 'seed'))."\n";
        if ($m) {
            $md .= "Model: {$m['provider']} / {$m['model']} (driver: {$m['driver']})\n";
            $md .= "Host: {$m['host']}\n";
        }
        $calls   = array_sum(array_column(array_column($reports, 'ai_stats'), 'ai_calls'));
        $invalid = array_sum(array_column(array_column($reports, 'ai_stats'), 'ai_invalid'));
        $errors  = array_sum(array_column(array_column($reports, 'ai_stats'), 'ai_errors'));
        $md .= "AI transport totals: calls={$calls}, unparseable={$invalid}, errors={$errors}\n\n";

        $md .= "| Seed | Events | TP | FP | TN | FN | Accuracy | Recall | FPR | F1 | AI calls |\n";
        $md .= "|---|---|---|---|---|---|---|---|---|---|---|\n";
        foreach ($reports as $r) {
            $o = $r['overall'];
            $md .= sprintf(
                "| %d | %d | %d | %d | %d | %d | %s | %s | %s | %s | %d |\n",
                $r['seed'], $o['events'], $o['tp'], $o['fp'], $o['tn'], $o['fn'],
                $o['accuracy'] ?? '-', $o['recall'] ?? '-', $o['fpr'] ?? '-', $o['f1'] ?? '-', $o['ai_invocations']
            );
        }
        $md .= sprintf(
            "| **mean ± std** | %d | %d | %d | %d | %d | %s | %s | %s | %s | %d |\n",
            $aggregate['sum_events'], $aggregate['sum_tp'], $aggregate['sum_fp'],
            $aggregate['sum_tn'], $aggregate['sum_fn'],
            $this->ms($aggregate['accuracy']), $this->ms($aggregate['recall']),
            $this->ms($aggregate['fpr']), $this->ms($aggregate['f1']),
            $aggregate['sum_ai_invocations']
        );
        $md .= "\nSame generators, different RNG draws per seed; report mean ± std ";
        $md .= "(sample standard deviation, n = {$aggregate['runs']}) in the paper.\n";
        $md .= "Full per-seed confusion matrices: eval-{$mode}-multi-{$stamp}.json\n";

        file_put_contents("{$dir}/eval-{$mode}-multi-{$stamp}.md", $md);
        $this->line("Multi-seed report written: {$dir}/eval-{$mode}-multi-{$stamp}.md");
    }

    private function ms(array $stat): string
    {
        if ($stat['mean'] === null) {
            return '-';
        }

        return number_format($stat['mean'], 4).' ± '.number_format($stat['std'], 4);
    }

    private function renderAggregateTable(array $reports, array $aggregate): void
    {
        $rows = [];
        foreach ($reports as $r) {
            $o = $r['overall'];
            $rows[] = [
                $r['seed'], $o['events'], $o['tp'], $o['fp'], $o['tn'], $o['fn'],
                $o['accuracy'] ?? '-', $o['recall'] ?? '-', $o['fpr'] ?? '-', $o['f1'] ?? '-', $o['ai_invocations'],
            ];
        }
        $rows[] = [
            'mean±std', $aggregate['sum_events'], $aggregate['sum_tp'], $aggregate['sum_fp'],
            $aggregate['sum_tn'], $aggregate['sum_fn'],
            $this->ms($aggregate['accuracy']), $this->ms($aggregate['recall']),
            $this->ms($aggregate['fpr']), $this->ms($aggregate['f1']), $aggregate['sum_ai_invocations'],
        ];

        $this->table(['Seed', 'Events', 'TP', 'FP', 'TN', 'FN', 'Acc', 'Recall', 'FPR', 'F1', 'AI'], $rows);
    }

    private function renderTable(array $report): void
    {
        $rows = [];
        foreach ($report['scenarios'] as $key => $m) {
            $rows[] = [
                $key, $m['events'], $m['tp'], $m['fp'], $m['tn'], $m['fn'],
                $m['accuracy'] ?? '-', $m['recall'] ?? '-', $m['fpr'] ?? '-',
                $m['challenge_denials'], $m['legit_challenged'], $m['ai_invocations'],
                $m['ai_never_invoked_fraction'] !== null ? number_format($m['ai_never_invoked_fraction'] * 100, 1).'%' : '-',
            ];
        }
        $o = $report['overall'];
        $rows[] = [
            'OVERALL', $o['events'], $o['tp'], $o['fp'], $o['tn'], $o['fn'],
            $o['accuracy'], $o['recall'], $o['fpr'],
            $o['challenge_denials'], $o['legit_challenged'], $o['ai_invocations'],
            $o['ai_never_invoked_fraction'] !== null ? number_format($o['ai_never_invoked_fraction'] * 100, 1).'%' : '-',
        ];

        $this->table(['Scenario', 'Events', 'TP', 'FP', 'TN', 'FN', 'Acc', 'Recall', 'FPR', 'Chal', 'LegitChal', 'AI calls', 'No-AI %'], $rows);
    }
}
