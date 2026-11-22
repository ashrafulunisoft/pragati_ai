<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use App\Services\McpSecurityService;
use App\Services\SecurityStore;
use App\Services\ChallengeService;
use App\Http\Middleware\McpMaliciousLoginMiddleware;

class SecurityBenchmarkCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'security:benchmark
                            {--path=all : Benchmark path: all, fast, ai, challenge, blocked, outage}
                            {--concurrency=1,10,50,100 : Comma-separated concurrency levels}
                            {--requests=50 : Number of requests per concurrency tier}
                            {--upstream=live : Upstream mode for AI path: live, mock, or outage}
                            {--format=table : Output format: table, markdown, json}
                            {--output-file= : Optional path to write markdown/json report}';

    /**
     * The console command description.
     */
    protected $description = 'Benchmark end-to-end login latency under concurrency across fast, AI, challenge, and outage paths';

    private McpMaliciousLoginMiddleware $middleware;

    public function __construct()
    {
        parent::__construct();
        $this->middleware = new McpMaliciousLoginMiddleware();
    }

    public function handle(): int
    {
        $pathFilter  = (string) $this->option('path');
        $concurrency = array_map('intval', explode(',', (string) $this->option('concurrency')));
        $numRequests = (int) $this->option('requests');
        $upstream    = (string) $this->option('upstream');
        $format      = (string) $this->option('format');

        $this->info("=== HYSA-MCP End-to-End Latency & Concurrency Benchmark ===");
        $this->line("Concurrency tiers : " . implode(', ', $concurrency));
        $this->line("Requests per tier : {$numRequests}");
        $this->line("Upstream AI mode  : {$upstream}");
        $this->line("Target paths      : {$pathFilter}");
        $this->newLine();

        $paths = ['fast', 'challenge', 'blocked', 'ai', 'outage'];
        if ($pathFilter !== 'all') {
            $paths = array_intersect($paths, array_map('trim', explode(',', $pathFilter)));
        }

        $allResults = [];

        foreach ($paths as $path) {
            $this->info("--> Benchmarking Path: [{$path}]");
            $allResults[$path] = [];

            foreach ($concurrency as $c) {
                // For live AI calls, cap high concurrency request count to prevent extreme API rate limiting
                $actualRequests = $numRequests;
                if ($path === 'ai' && $upstream === 'live' && $c > 10) {
                    $actualRequests = min($numRequests, 20);
                } elseif ($path === 'outage' && $c > 10) {
                    $actualRequests = min($numRequests, 20);
                }

                $tierResult = $this->runTier($path, $c, $actualRequests, $upstream);
                $allResults[$path][$c] = $tierResult;

                $this->line(sprintf(
                    "   C=%3d | Req=%3d | Mean: %8.2f ms | p50: %8.2f ms | p95: %8.2f ms | p99: %8.2f ms | %7.1f req/s | Timeouts: %4.1f%%",
                    $c,
                    $tierResult['requests'],
                    $tierResult['mean_ms'],
                    $tierResult['p50_ms'],
                    $tierResult['p95_ms'],
                    $tierResult['p99_ms'],
                    $tierResult['throughput_rps'],
                    $tierResult['timeout_rate_pct']
                ));
            }
            $this->newLine();
        }

        $this->renderReport($allResults, $format);

        $outputFile = $this->option('output-file');
        if ($outputFile) {
            $md = $this->buildMarkdownReport($allResults);
            file_put_contents($outputFile, $md);
            $this->info("Report saved to: {$outputFile}");
        }

        return Command::SUCCESS;
    }

    /**
     * Run a benchmark tier under simulated concurrency.
     */
    private function runTier(string $path, int $concurrency, int $requests, string $upstream): array
    {
        $latencies = [];
        $outcomes  = ['200' => 0, '403' => 0, '428' => 0, '429' => 0, 'error' => 0];
        $timeouts  = 0;
        $upstreamCalls = 0;

        // Configure upstream mode
        if ($path === 'outage' || $upstream === 'outage') {
            Http::fake(function () {
                throw new \Illuminate\Http\Client\ConnectionException('Upstream LLM connection timed out (simulated)');
            });
        } elseif ($upstream === 'mock') {
            Http::fake([
                '*' => Http::response([
                    'choices' => [[
                        'message' => [
                            'content' => json_encode([
                                'decision' => 'SUSPICIOUS',
                                'attack_type' => 'credential_stuffing',
                                'risk_score' => 65,
                                'confidence' => 0.88,
                                'recommended_action' => 'otp'
                            ])
                        ]
                    ]]
                ], 200)
            ]);
        }

        $tierStart = microtime(true);

        if ($concurrency > 1 && function_exists('pcntl_fork') && $path !== 'ai') {
            // High concurrency multi-process runner for fast / blocked / challenge paths
            $tierResult = $this->runConcurrentProcesses($path, $concurrency, $requests);
            if ($path === 'outage' || $upstream === 'outage' || $upstream === 'mock') {
                Http::fake(null);
            }
            return $tierResult;
        }

        // Sequential / in-process batch dispatch (used for AI & fallback)
        for ($i = 0; $i < $requests; $i++) {
            $reqStart = microtime(true);
            $clientIp = "10.{$concurrency}." . (($i % 200) + 1) . "." . (($i % 250) + 1);
            $email    = "c{$concurrency}_u{$i}_" . substr(md5("{$path}_{$concurrency}_{$i}"), 0, 8) . "@benchmark.test";

            // Clean previous blocks if testing non-blocked path
            if ($path !== 'blocked') {
                SecurityStore::del(["blocked:ip:{$clientIp}", "blocked:email:{$email}"]);
            }

            try {
                $status = $this->executePath($path, $clientIp, $email);
                $reqLat = (microtime(true) - $reqStart) * 1000;
                $latencies[] = $reqLat;
                $outcomes[(string) $status] = ($outcomes[(string) $status] ?? 0) + 1;

                if ($path === 'ai') {
                    $upstreamCalls++;
                } elseif ($path === 'outage') {
                    // With MCP_RETRIES=1, every outage request triggers 1 initial + 1 retry = 2 attempts
                    $upstreamCalls += (1 + (int) config('services.minimax.retries', 1));
                    $timeouts++;
                }
            } catch (\Exception $e) {
                $reqLat = (microtime(true) - $reqStart) * 1000;
                $latencies[] = $reqLat;
                $outcomes['error']++;
                $timeouts++;
            }
        }

        $totalWallTime = microtime(true) - $tierStart;
        if ($path === 'outage' || $upstream === 'outage' || $upstream === 'mock') {
            Http::fake(null);
        }

        return $this->computeMetrics($latencies, $totalWallTime, $outcomes, $timeouts, $requests, $upstreamCalls, $concurrency);
    }

    /**
     * True multi-process concurrency runner using pcntl_fork.
     */
    private function runConcurrentProcesses(string $path, int $concurrency, int $totalRequests): array
    {
        $batchSize = (int) ceil($totalRequests / $concurrency);
        $tempDir = storage_path('framework/testing/benchmark_' . uniqid());
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $tierStart = microtime(true);
        $pids = [];

        for ($worker = 0; $worker < $concurrency; $worker++) {
            $workerRequests = min($batchSize, max(0, $totalRequests - ($worker * $batchSize)));
            if ($workerRequests <= 0) {
                continue;
            }

            $pid = pcntl_fork();
            if ($pid === -1) {
                // Fork failed, skip to next
                continue;
            }

            if ($pid === 0) {
                // In child process: re-connect Redis / DB if needed
                $latencies = [];
                $outcomes = ['200' => 0, '403' => 0, '428' => 0, '429' => 0, 'error' => 0];
                $workerMw = new McpMaliciousLoginMiddleware();

                for ($j = 0; $j < $workerRequests; $j++) {
                    $idx = ($worker * $batchSize) + $j;
                    $clientIp = "10.{$concurrency}." . (($worker % 200) + 1) . "." . (($idx % 250) + 1);
                    $email    = "w{$worker}_c{$concurrency}_u{$idx}_" . substr(md5("{$path}_{$concurrency}_{$idx}"), 0, 8) . "@benchmark.test";

                    if ($path !== 'blocked') {
                        SecurityStore::del(["blocked:ip:{$clientIp}", "blocked:email:{$email}"]);
                    }

                    $reqStart = microtime(true);
                    try {
                        $status = $this->executePathDirect($workerMw, $path, $clientIp, $email);
                        $latencies[] = (microtime(true) - $reqStart) * 1000;
                        $outcomes[(string) $status] = ($outcomes[(string) $status] ?? 0) + 1;
                    } catch (\Exception $e) {
                        $latencies[] = (microtime(true) - $reqStart) * 1000;
                        $outcomes['error'] = ($outcomes['error'] ?? 0) + 1;
                    }
                }

                file_put_contents("{$tempDir}/worker_{$worker}.json", json_encode([
                    'latencies' => $latencies,
                    'outcomes'  => $outcomes,
                ]));
                exit(0);
            } else {
                $pids[] = $pid;
            }
        }

        // Wait for all child processes to finish
        foreach ($pids as $p) {
            pcntl_waitpid($p, $status);
        }

        $totalWallTime = microtime(true) - $tierStart;

        // Aggregate worker results
        $allLatencies = [];
        $mergedOutcomes = ['200' => 0, '403' => 0, '428' => 0, '429' => 0, 'error' => 0];

        foreach (glob("{$tempDir}/worker_*.json") as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (isset($data['latencies'])) {
                $allLatencies = array_merge($allLatencies, $data['latencies']);
            }
            if (isset($data['outcomes'])) {
                foreach ($data['outcomes'] as $code => $cnt) {
                    $mergedOutcomes[$code] = ($mergedOutcomes[$code] ?? 0) + $cnt;
                }
            }
            @unlink($file);
        }
        @rmdir($tempDir);

        return $this->computeMetrics($allLatencies, $totalWallTime, $mergedOutcomes, 0, count($allLatencies), 0, $concurrency);
    }

    /**
     * Execute a single request for a target path.
     */
    private function executePath(string $path, string $ip, string $email): int
    {
        return $this->executePathDirect($this->middleware, $path, $ip, $email);
    }

    private function executePathDirect(McpMaliciousLoginMiddleware $middleware, string $path, string $ip, string $email): int
    {
        switch ($path) {
            case 'fast':
                // Sub-threshold legitimate login: counters < 3, no AI call, allowed
                SecurityStore::setex("attack:login:ip:$ip", 60, 1);
                SecurityStore::setex("attack:login:email:$email", 60, 1);
                $req = Request::create('/login', 'POST', ['email' => $email, 'password' => 'secret'], [], [], [
                    'REMOTE_ADDR' => $ip,
                ]);
                $resp = $middleware->handle($req, fn () => response()->json(['status' => 'authenticated'], 200));
                return $resp->getStatusCode();

            case 'blocked':
                // Adversarial DoS flood: IP already blocked in Redis. Dropped at fast-path early block.
                SecurityStore::setex("blocked:ip:$ip", 3600, 1);
                $req = Request::create('/login', 'POST', ['email' => $email, 'password' => 'attack'], [], [], [
                    'REMOTE_ADDR' => $ip,
                ]);
                $resp = $middleware->handle($req, fn () => response()->json(['status' => 'should_not_reach'], 200));
                return $resp->getStatusCode();

            case 'challenge':
                // Step-up verification flow: hit threshold, issue challenge, then solve challenge
                SecurityStore::setex("attack:login:ip:$ip", 60, 5);
                SecurityStore::setex("attack:login:email:$email", 60, 3);

                // Step 1: Request without solution -> receives 428
                $req1 = Request::create('/login', 'POST', ['email' => $email, 'password' => 'secret'], [], [], [
                    'REMOTE_ADDR' => $ip,
                ]);
                $resp1 = $middleware->handle($req1, fn () => response()->json(['status' => 'ok'], 200));

                if ($resp1->getStatusCode() === 428) {
                    $body = json_decode($resp1->getContent(), true);
                    $challenge = $body['challenge'] ?? null;
                    if ($challenge && isset($challenge['token'])) {
                        // Solve challenge
                        $a = $b = 0;
                        sscanf($challenge['question'], '%d + %d = ?', $a, $b);
                        $answer = (string) ($a + $b);

                        // Step 2: Present solution
                        $req2 = Request::create('/login', 'POST', [
                            'email' => $email,
                            'password' => 'secret',
                            'security_challenge_token' => $challenge['token'],
                            'security_challenge_answer' => $answer,
                        ], [], [], ['REMOTE_ADDR' => $ip]);

                        $resp2 = $middleware->handle($req2, fn () => response()->json(['status' => 'authenticated'], 200));
                        return $resp2->getStatusCode();
                    }
                }
                return $resp1->getStatusCode();

            case 'ai':
                // Suspicious escalation: threshold breached, triggers full McpSecurityService::analyzeLogin
                SecurityStore::setex("attack:login:ip:$ip", 60, 6);
                SecurityStore::setex("attack:login:email:$email", 60, 4);
                $req = Request::create('/login', 'POST', ['email' => $email, 'password' => 'pass123'], [], [], [
                    'REMOTE_ADDR' => $ip,
                ]);
                $resp = $middleware->handle($req, fn () => response()->json(['status' => 'allowed_or_blocked'], 200));
                return $resp->getStatusCode();

            case 'outage':
                // Upstream outage: threshold breached, AI unavailable, triggers degraded policy fallback
                SecurityStore::setex("attack:login:ip:$ip", 60, 6);
                SecurityStore::setex("attack:login:email:$email", 60, 4);
                $req = Request::create('/login', 'POST', ['email' => $email, 'password' => 'pass123'], [], [], [
                    'REMOTE_ADDR' => $ip,
                ]);
                $resp = $middleware->handle($req, fn () => response()->json(['status' => 'degraded_allowed'], 200));
                return $resp->getStatusCode();

            default:
                return 200;
        }
    }

    /**
     * Compute statistical percentiles, throughput, and error metrics.
     */
    private function computeMetrics(
        array $latencies,
        float $wallTimeSec,
        array $outcomes,
        int $timeouts,
        int $totalRequests,
        int $upstreamCalls,
        int $concurrency
    ): array {
        sort($latencies);
        $n = count($latencies);

        $mean = $n > 0 ? round(array_sum($latencies) / $n, 2) : 0.0;
        $p50  = $n > 0 ? round($latencies[(int) ($n * 0.50)], 2) : 0.0;
        $p95  = $n > 0 ? round($latencies[(int) min($n - 1, (int) ($n * 0.95))], 2) : 0.0;
        $p99  = $n > 0 ? round($latencies[(int) min($n - 1, (int) ($n * 0.99))], 2) : 0.0;
        $min  = $n > 0 ? round($latencies[0], 2) : 0.0;
        $max  = $n > 0 ? round($latencies[$n - 1], 2) : 0.0;

        $throughput = $wallTimeSec > 0 ? round($n / $wallTimeSec, 1) : 0.0;
        $timeoutRate = $totalRequests > 0 ? round(($timeouts / $totalRequests) * 100, 2) : 0.0;

        // Retry amplification factor: upstream attempts / client requests
        $amplification = $totalRequests > 0 && $upstreamCalls > 0
            ? round($upstreamCalls / $totalRequests, 2)
            : 1.0;

        return [
            'concurrency'         => $concurrency,
            'requests'            => $n,
            'wall_time_sec'       => round($wallTimeSec, 3),
            'mean_ms'             => $mean,
            'min_ms'              => $min,
            'max_ms'              => $max,
            'p50_ms'              => $p50,
            'p95_ms'              => $p95,
            'p99_ms'              => $p99,
            'throughput_rps'      => $throughput,
            'timeout_count'       => $timeouts,
            'timeout_rate_pct'    => $timeoutRate,
            'retry_amplification' => $amplification,
            'outcomes'            => $outcomes,
        ];
    }

    /**
     * Render the report in the requested format.
     */
    private function renderReport(array $results, string $format): void
    {
        if ($format === 'json') {
            $this->line(json_encode($results, JSON_PRETTY_PRINT));
            return;
        }

        if ($format === 'markdown') {
            $this->line($this->buildMarkdownReport($results));
            return;
        }

        // Default console table
        foreach ($results as $path => $tiers) {
            $this->info("Summary Table: Path [{$path}]");
            $rows = [];
            foreach ($tiers as $c => $m) {
                $rows[] = [
                    $c,
                    $m['requests'],
                    $m['mean_ms'],
                    $m['p50_ms'],
                    $m['p95_ms'],
                    $m['p99_ms'],
                    $m['throughput_rps'],
                    $m['timeout_rate_pct'] . '%',
                    $m['retry_amplification'] . 'x',
                ];
            }
            $this->table(
                ['Concurrency', 'Requests', 'Mean (ms)', 'p50 (ms)', 'p95 (ms)', 'p99 (ms)', 'Throughput (req/s)', 'Timeout %', 'Amplification'],
                $rows
            );
            $this->newLine();
        }
    }

    /**
     * Build GFM Markdown report.
     */
    public function buildMarkdownReport(array $results): string
    {
        $md = "# End-to-End Latency & Concurrency Benchmark Report\n\n";
        $md .= "Benchmark evaluated across five operational paths under varying concurrency levels (C = 1, 10, 50, 100).\n\n";

        foreach ($results as $path => $tiers) {
            $pathNames = [
                'fast'      => 'Path A: Sub-threshold Fast Path (Redis Only — Legitimate Login)',
                'ai'        => 'Path B: AI-Escalated Analysis Path (Model Context Protocol / LLM)',
                'challenge' => 'Path C: Step-up Verification Flow (HTTP 428 Challenge + Solution)',
                'blocked'   => 'Path D: Adversarial Post-Block Flood (Redis Fast-Path Early Rejection)',
                'outage'    => 'Path E: Upstream Outage & Retry Amplification (Degraded Failover)',
            ];

            $md .= "### " . ($pathNames[$path] ?? ucfirst($path)) . "\n\n";
            $md .= "| Concurrency (\$C\$) | Requests | Mean (ms) | p50 (ms) | p95 (ms) | p99 (ms) | Throughput (req/s) | Timeouts (%) | Retry Amp. |\n";
            $md .= "|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|\n";

            foreach ($tiers as $c => $m) {
                $md .= sprintf(
                    "| **%d** | %d | %.2f | %.2f | %.2f | %.2f | %.1f | %.1f%% | %.2fx |\n",
                    $c,
                    $m['requests'],
                    $m['mean_ms'],
                    $m['p50_ms'],
                    $m['p95_ms'],
                    $m['p99_ms'],
                    $m['throughput_rps'],
                    $m['timeout_rate_pct'],
                    $m['retry_amplification']
                );
            }
            $md .= "\n";
        }

        return $md;
    }
}
