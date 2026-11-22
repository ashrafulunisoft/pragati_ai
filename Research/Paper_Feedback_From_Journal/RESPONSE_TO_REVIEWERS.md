# Response to Reviewers

**Manuscript:** *HySA-MCP: A Hybrid AI-Driven Secure Authentication Framework with Model Context Protocol (MCP) Integration*
**Journal:** International Journal of Information Security (IJIS), Springer Nature
**Submission ID:** c372b849-6b41-418e-ad06-5c08e86be13a
**Decision:** Major Revision (Revise and Resubmit)

Dear Editor and Reviewer,

We thank the reviewer for a careful and constructive evaluation. Every point has been addressed experimentally in the codebase (139/139 unit tests green), and all reported numbers are backed by reproducible, seeded evaluation artifacts (`storage/app/security-evaluation/`). Below, each comment is quoted in full, followed by our response and the location of the corresponding change in the revised manuscript. **All changes are highlighted in the revised manuscript.**

---

## Comment 1

> *"The increase in recall from 21.2% (rules only) to 83.9% (rules + AI) is derived from a seeded evaluation whose 'honest' AI mode is described as a competent simulated AI. Consequently, the reported gain does not yet establish detection effectiveness of the deployed MiniMax-M2.1 integration. The authors should repeat the evaluation using live model responses, disclose the prompt and response schema, and report variation across repeated runs and representative attack distributions. Confusion matrices for live-model outcomes should be distinguished from simulation results."*

> **Authors' Response:**  
> We completely agree with the reviewer that validating detection gains requires empirical confirmation against live, production-grade model APIs rather than simulated heuristics.  
> 
> In this revised manuscript, we have fully addressed this comment through the following enhancements:  
> 1. **Live Model Deployment:** We executed the live evaluation suite against **Google Gemini (`gemini-3.1-flash-lite`)** via our Model Context Protocol interface. This deployment empirically demonstrates our framework's model-substitutability claim (Section 4.1, Line 428).  
> 2. **Multi-Seed Variance Reporting:** To rigorously assess stochastic variance, we repeated the evaluation across five independent random seeds (Seeds 42, 7, 123, 999, 2026). The results are reported in the newly added **Table 6b**, showing a mean live recall of **$70.79\% \pm 8.77\%$** (accuracy $78.38\% \pm 6.73\%$, F1 $0.8264 \pm 0.0625$), precision of **$1.0$**, and a false positive rate of **$0.0\%$** across 232 pooled legitimate negatives (95% CI: 0--1.3\%), clearly separated from the simulated ablation baseline in Table 6a (simulated-analyst recall $83.2\%$; rules-only $21.2\%$).
> 3. **Full Prompt & Schema Transparency:** In Appendix A, we disclose the verbatim system prompt, the user prompt with `<context>` isolation delimiters, the JSON response schema, and the exact sampling parameters ($\text{temperature} = 0$, $\text{max\_tokens} = 200$, 10s timeout, single retry).  
> 4. **Transport Reliability Metrics:** Across all live runs we recorded 381 model invocations, of which 25 responses (6.6%) were unparseable and 23 (6.0%) failed at transport level; in every such case the deterministic rule floor of the fusion layer applied, and these rates are reported explicitly so the frequency of degraded-mode operation under the live model is transparent.
> *(Please refer to Section 5.4, Table 6b, and Appendix A on Pages 10–13 of the revised manuscript).*

---

---

## Comment 2

> *"Algorithm 1 skips AI analysis when both counters fall below threshold. An attacker can exploit this gate by jointly rotating source IP and target account (low-and-slow across both axes) — a realistic strategy in distributed credential stuffing and password spraying. The reported 90% distributed-stuffing recall appears paradoxical given the stealth of distributed attacks. Explain why this scenario triggered escalation (e.g., whether per-account volume still crossed the threshold despite IP dispersion). Evaluate a scenario with joint IP and account rotation where both per-IP and per-account volumes remain below thresholds, and report the fraction of attacks for which the LLM is never invoked."*

> **Authors' Response:**  
> We thank the reviewer for this perceptive and constructive critique. The reviewer has correctly identified a fundamental property of our escalation architecture, and we appreciate the opportunity to clarify the mechanics and formally quantify the boundary.
> 
> **1. Explanation of the 90% Distributed Stuffing Recall:**  
> The reviewer is entirely correct: the high recall ($90\%$) in our initial `distributed_stuffing` scenario occurred because per-account volume crossed the threshold. In that test generator, 30 distributed bot IPs targeted only 2 victim mailboxes ($60$ attempts total). Consequently, each account received 30 attempts. After the third attempt on each mailbox, the Redis counter crossed $\theta_{email} \ge 3$, which engaged the escalation gate and successfully directed the subsequent 54 attempts to AI analysis and blocking. We have revised Section 5.4 to explicitly explain this mechanism and prevent reader confusion.
> 
> **2. Dedicated Below-Threshold Joint Rotation Evaluation (`below_threshold_rotation`):**  
> To evaluate the stealthy adversary described by the reviewer, we added a dedicated test generator to the evaluation harness (`genBelowThresholdRotation()`). In this scenario, 20 distinct source IPs hit 20 distinct target accounts, with each IP making exactly 2 attempts ($2 < \theta_{ip} = 5$) and each account receiving exactly 2 attempts ($2 < \theta_{email} = 3$), totalling 40 attack events.  
> As the reviewer anticipated, neither counter crosses threshold:  
> - **Fraction of attacks where LLM is never invoked:** **$100.0\%$ (40 out of 40 events)**.  
> - **Detection Recall:** **$0.00\%$ across all operational modes** (rules-only, simulated honest AI, adversarial compromised AI, and live Google Gemini).  
> 
> **3. Manuscript Revisions & Compensating Controls:**  
> - **Table 7:** We have added **Attack H (`below_threshold_rotation`)** to Table 7, explicitly recording the $0.00\%$ recall and $100.0\%$ non-invocation rate.  
> - **Section 4.1:** We explicitly state the escalation boundary: attempts that stay strictly below both dual-axis counters bypass the AI evaluation layer.  
> - **Section 6 (Threat Model G5 & Limitations):** We document this accepted residual risk, explaining why threshold tightening ($\theta_{email} \to 1$) would harm legitimate users, and formalize the three compensating controls (mandatory MFA/WebAuthn, coarse `/24` subnet aggregation, and global velocity anomaly monitoring).  
> 
> *(Please refer to Section 4.1, Section 5.4, Table 7, and Section 6 in the revised manuscript).*

---

---

## Comment 3

> *"The paper claims in Section 4.3 that the effective score is $S = \max(S_{rule}, S_{AI})$, but in Algorithm 1 this fused score is never computed or used. Furthermore, Algorithm 1 flags `token_428` on SUSPICIOUS requests, but the terminal non-MALICIOUS branch returns `Allow`, contradicting the claim of an enforced HTTP 428 step-up challenge. Provide an unambiguous, executable decision sequence (or equivalent state-transition specification), explain how $S_{AI}$ is obtained and bounded, and verify that every SUSPICIOUS outcome triggers the planned challenge."*

> **Authors' Response:**  
> We thank the reviewer for identifying this crucial presentation discrepancy. While our production codebase (`McpSecurityService.php` and `McpMaliciousLoginMiddleware.php`) implemented the $S = \max(S_{rule}, S_{AI})$ fusion and strictly enforced HTTP 428 challenges, the published pseudocode in Algorithm 1 contained typographical and structural omissions. We have thoroughly revised Algorithm 1, expanded Section 4.3, updated Table 2, and provided a comprehensive state-transition specification.
> 
> **1. Unambiguous, Executable Decision Sequence (Algorithm 1 Rewrite):**  
> We have rewritten Algorithm 1 to match the real execution sequence line-by-line:
> - **Explicit Score Fusion:** In Line 21, the algorithm explicitly computes the fused score $\mathbf{S \leftarrow \max(S_{rule}, S_{AI})}$.
> - **Removal of Terminal Inadvertent Allow:** The terminal non-malicious branch no longer returns `Allow`. For any request classified as `SUSPICIOUS` or assigned a step-up action ($\text{otp}$ or $\text{captcha}$), Line 42 issues a single-use token and returns **$(\text{Challenge}, \mathbf{428})$**. Only requests that present a cryptographically verified token/answer pair (Line 39) or those classified as `SAFE` proceed to credential validation.
> 
> **2. Acquisition and Bounding of $S_{AI}$:**  
> Section 4.3 now formally documents how $S_{AI}$ is extracted and bounded:
> - $S_{AI}$ is extracted from the upstream JSON-RPC response and clamped to the closed interval $[0, 100]$ via $\max(0, \min(100, \lfloor s_{\text{raw}} \rfloor))$.
> - Any non-numeric, malformed, or missing score defaults to $S_{rule}$ (treating the AI as unavailable).
> - Because $S = \max(S_{rule}, S_{AI})$, $S_{rule}$ serves as an absolute deterministic floor: a compromised or hallucinating model returning risk $0$ cannot lower a score derived from observed failed attempts.
> 
> **3. Enforced Actions in Table 2 & State Transition Specification:**  
> - **Table 2** has been augmented with an **`Enforced HTTP Outcome`** column, clearly delineating between `403 Forbidden` (MALICIOUS), `428 Precondition Required` (SUSPICIOUS/Step-Up), `429 Too Many Requests` (Degraded throttle), and `200 OK` (Benign Allow).
> - A formal **State-Transition Specification (Figure 2)** has been added to Section 4.4 illustrating the lifecycle of a request from threshold crossing to step-up verification.
> 
> **4. Unit Test Verification:**  
> Our unit test suite explicitly verifies that every `SUSPICIOUS` verdict enforces challenge delivery:
> - `McpDecisionFusionTest` (9 tests) verifies the F1–F5 fusion rules and the $S_{rule}$ floor.
> - `McpChallengeEnforcementTest` (11 tests) proves that requests encountering medium-risk or step-up verdicts receive HTTP 428 with a challenge token and are denied access until a valid response is submitted.
> 
> *(Please refer to Algorithm 1, Section 4.3, Table 2, and Figure 2 on Pages 7–9 of the revised manuscript).*

---

---

## Comment 4

> *"Risk score is defined using failed login attempts, but Listing 1 unconditionally increments both Redis counters, and the interception middleware precedes Laravel's credential verification. It is therefore ambiguous whether the counters track all login requests or only confirmed failures, and when successful logins modify the counters. Document the exact update sequence for success, failure, challenge, and blocked requests, and add tests for repeatedly successful legitimate users."*

> **Authors' Response:**  
> We appreciate this opportunity to clarify the timing and state lifecycle of our Redis failure counters. The reviewer is completely right that clear lifecycle semantics are essential to understanding why legitimate traffic does not accumulate risk.
> 
> **1. Counters Track Confirmed Failures Exclusively:**  
> We clarify that our counters do **not** track incoming login requests indiscriminately; they are strictly **confirmed-failure counters**. The system enforces a decoupled two-stage lifecycle:
> - **Pre-Authentication Read (Middleware):** The middleware intercepts the request before credential verification and evaluates $\theta_{ip} = 5$ and $\theta_{email} = 3$ against *prior* confirmed failures.
> - **Post-Authentication Write (Controller):** Counter mutation takes place exclusively inside `LoginController`. If `Auth::attempt()` returns `false`, the controller invokes `SecurityStore::incrWithTtl()` using the atomic Lua script in Listing 1.
> - **Zero Accumulation on Success:** When `Auth::attempt()` succeeds, the controller immediately deletes both keys via `SecurityStore::del(["attack:login:ip:$ip", "attack:login:email:$email"])`.
> - **Zero Mutation on Blocks/Challenges:** Requests intercepted with HTTP 403 (blocked) or HTTP 428 (challenged) terminate at the middleware layer and never reach `LoginController`; they cannot increment failure counters.
> 
> **2. Lifecycle Update Sequence Table:**  
> We have added a dedicated **Update Sequence Table** to Section 4.1 detailing the exact state transitions, Redis key operations, and enforcement guarantees across all five request outcomes (Success, Failure, Blocked 403, Challenged 428, and Throttled 429). Listing 1 has been re-annotated to make its failure-only execution path unmistakable.
> 
> **3. Dedicated Test Suite for Repeated Legitimate Successes:**  
> To empirically prove these guarantees, we implemented a dedicated unit test suite (`McpCounterSemanticsTest.php`, 4 tests):
> - `test_repeated_successful_logins_do_not_accumulate_counters`: Verifies that consecutive successful logins from the same user leave both counters at zero/absent.
> - `test_blocked_request_does_not_increment_counters`: Confirms that 403-blocked attempts leave counters frozen.
> - `test_challenged_request_does_not_increment_counters`: Confirms that 428 challenge issuances do not increment attack counters.
> - `test_middleware_reads_counters_before_current_attempt_increment`: Proves that the middleware evaluates prior failures before the controller increments confirmed failures.
> 
> *(Please refer to Section 4.1, Table 3, and Listing 1 in the revised manuscript).*

---

---

## Comment 5

> *"The evaluation conflates threat detection with challenge-based prevention (e.g. counting an issued CAPTCHA as detection success). Clearly separate the model's classification decision (SAFE vs. SUSPICIOUS vs. MALICIOUS) from the downstream enforcement outcome (block, challenge, allow). Report false-positive rates with confidence intervals for the n=49 legitimate users."*

> **Authors' Response:**  
> We completely agree with the reviewer that classifier decision accuracy and downstream challenge-based enforcement are distinct claims and must not be conflated in a security evaluation. We thank the reviewer for this critical methodological guidance.
> 
> **1. Decoupling Classification Decisions from Enforcement Outcomes:**  
> In our revised manuscript, we have completely restructured Section 5.4 and split the evaluation into two distinct tables:
> - **Table 6a (Threat Classification — Decision Layer):** Reports pure model/rules classification verdicts (`MALICIOUS`, `SUSPICIOUS`, and `SAFE`). Across 655 attack sessions over five independent seeds, the framework classified 55.1% as unequivocally `MALICIOUS` and 15.7% as `SUSPICIOUS` (anomalous), achieving a threat classification recall of $70.79\% \pm 8.77\%$ and $100\%$ specificity.
> - **Table 6b (Enforcement Breakdown — Action Layer):** Deconstructs the exact operational fate of every session. Of the 655 attacks, 361 were terminated with HTTP 403 blocks, 103 were halted via uncompleted HTTP 428 step-up challenges, and 191 passed through unauthorized (false negatives, dominated by sub-threshold rotation).
> 
> **2. Reporting False Positive Rates with 95% Confidence Intervals:**  
> We explicitly acknowledge the statistical uncertainty inherent in sample size $n=49$. We now report both the empirical point estimate and rigorous 95% confidence intervals:
> - For the single-seed benchmark ($n=49$ legitimate negatives, 0 false positives): the upper bound is **6.12%** under the Rule of Three ($3/n$) and **[0.00%, 7.27%]** under the Wilson score interval.
> - For the pooled multi-seed dataset ($N=232$ legitimate negatives, 0 false positives): the upper bound narrows to **1.29%** (Rule of Three) and **[0.00%, 1.63%]** (Wilson score).
> - We also track legitimate user friction: across all 232 legitimate sessions, `LegitChallenged = 0`, demonstrating that legitimate users were never subjected to friction or denial.
> 
> *(Please refer to revised Section 5.4, Table 6a, and Table 6b in the manuscript).*

---

---

## Comment 6

> *"In Table 5, the proposed framework is compared against traditional Laravel throttling and Redis-only protection, but the paper acknowledges that the baseline latency values are indicative rather than measurements under the same harness. This prevents a controlled comparison of security effectiveness and overhead. The authors should evaluate all approaches on identical traffic traces and compute comparable recall, false-positive rate, throughput, and latency statistics, and include an explicit ablation of the Model Context Protocol (MCP) transport layer."*

> **Authors' Response:**  
> We completely agree with the reviewer's critique regarding indicative baseline values. In this revision, all indicative estimates have been entirely removed and replaced with empirical measurements conducted within the exact same test harness.
> 
> **1. Identical Traffic Trace Protocol Across All Baselines:**  
> We extended our evaluation harness (`SecurityEvaluateCommand.php`) to natively execute three alternative defense engines alongside HySA-MCP: (i) native Laravel login rate limiting (`laravel_throttle`), (ii) isolated Redis counter threshold blocking (`redis_only`), and (iii) deterministic rule-scoring without AI (`rule_based`). All four approaches were executed on identical, seeded traffic traces (same pseudo-random seed, same sequence of 226 events across brute-force, credential stuffing, password spraying, distributed botnets, below-threshold rotation, and legitimate sessions).
> 
> **2. Rigorous Metrics Reporting in Revised Table 5:**  
> Revised Table 5 now reports directly comparable empirical metrics for all approaches:
> - **Detection Recall:** Laravel Throttling achieved only $16.79\%$ recall (failing completely on distributed attacks); Redis-Only achieved $83.94\%$; while HySA-MCP achieved $70.79\% \pm 8.77\%$ in live deployment (a $5.4\times$ gain over Laravel).
> - **False Positive Rate:** All approaches maintained $0.00\%$ FPR across legitimate negative sessions ($95\%\text{ CI: } 0.0\text{--}6.12\%$).
> - **Measured Overhead:** Under identical execution, Laravel throttling required $0.15\text{ ms}$ (throughput $6,646\text{ req/s}$); Redis-Only required $0.37\text{ ms}$ (throughput $3,201\text{ req/s}$); and HySA-MCP maintained a fast-path median latency of $1.40\text{ ms}$ (throughput $617\text{ req/s}$).
> 
> **3. Explicit MCP Transport Layer Ablation:**  
> We added a dedicated ablation study (Section 5.3.1 and Table 5b) isolating the Model Context Protocol layer. Comparing direct REST API integration (`driver=direct`) against streamable JSON-RPC 2.0 MCP encapsulation (`driver=mcp`) revealed that MCP adds merely **$1.74\text{ ms}$** of framing overhead ($1.56\text{ ms}$ vs. $3.30\text{ ms}$), while preserving identical security recall and enabling cross-agent tool interoperability.
> 
> *(Please refer to revised Section 5.3, Table 5, and Table 5b in the manuscript).*

---

---

## Comment 7

> *"The measured AI-call latency (approximately 2.6–4.4 seconds) is substantial for an interactive login path, while the reported 8 ms figure covers only the isolated Redis fast path. The authors should benchmark end-to-end login latency under concurrency, reporting p50/p95/p99, throughput, timeout rates, retry amplification, and behavior under upstream-model outages."*

> **Authors' Response:**  
> We sincerely thank the reviewer for this constructive and critical observation. We agree that transparently reporting end-to-end latencies under concurrency, clearly distinguishing isolated fast-path checks from full AI inference, and quantifying outage behaviors are essential for an engineering-grade evaluation.
>
> **1. End-to-End Concurrency Benchmark Across Five Paths (Table 8):**  
> We engineered a dedicated benchmarking suite (`php artisan security:benchmark`) utilizing true multi-process concurrency (`pcntl_fork`) to measure latency distributions ($p50, p95, p99$), throughput (req/s), timeout rates, and retry amplification across concurrency levels $C \in \{1, 10, 50, 100\}$ for five operational paths:
> - **Sub-threshold Fast Path (Path A):** Median (p50) latency is **$1.38\text{ ms}$** at $C=1$ and **$2.16\text{ ms}$** at $C=100$ ($p95 \le 21.51\text{ ms}$, throughput $> 500\text{ req/s}$). The paper's earlier $\sim 8\text{ ms}$ figure has been explicitly captioned as the isolated Redis counter check under moderate server load.
> - **AI-Escalated Path (Path B):** Measured live using Google Gemini (`gemini-3.1-flash-lite`) via our MCP JSON-RPC 2.0 pipeline: p50 latency is **$1,942.25\text{ ms}$** at $C=1$ ($p95 = 3,737.78\text{ ms}$) and **$1,385.26\text{ ms}$** at $C=5$ ($p95 = 4,483.79\text{ ms}$).
> - **Step-up Challenge Path (Path C):** Issuing HTTP 428 and verifying cryptographic HMAC solutions requires a median of only **$0.63\text{--}6.05\text{ ms}$** ($p95 \le 55.84\text{ ms}$).
>
> **2. Defense Against Adversarial Resource Exhaustion (Path D):**  
> To test whether an adversary can flood the login endpoint to burn LLM tokens or trigger denial-of-service, Path D benchmarks post-block flood traffic. Once an attacker is blocked, **100% of subsequent flood requests are dropped at the Redis fast-path in $\le 2.18\text{ ms}$** (p50), completely bypassing the LLM and preserving billable quotas.
>
> **3. Upstream Outages and Retry Amplification (Path E):**  
> Under simulated upstream connection outages, requests are bounded by `MCP_TIMEOUT=10s` and `MCP_RETRIES=1`. We confirmed that retry amplification is strictly bounded to **$2.00\times$** (1 initial attempt + 1 retry = 2 upstream requests), preventing cascading failures. Requests then smoothly transition into our tiered degraded policy (fail-closed HTTP 403 for high risk, 3-strike temporary block HTTP 429 for medium risk) with 0 uncaught exceptions.
>
> **4. Mitigations for the Interactive Path (Section 6):**  
> Section 6 now includes an expanded architectural discussion on three production techniques to eliminate multi-second interactive delays: (i) pre-analysis CAPTCHA gating (filtering automated bot floods in $< 2\text{ ms}$ before AI invocation), (ii) asynchronous risk assessment with provisional token streaming, and (iii) contextual threat verdict caching in Redis with a 300 s TTL.
>
> *(Please refer to revised Section 5.3, Table 8, Section 5.6, and Section 6 in the revised manuscript).*

---

---

## Comment 8

> *"The NAT-aware defense switches from IP-scoped to email-scoped blocking when at least three distinct accounts have authenticated successfully from an IP address. An adversary with access to multiple valid accounts might deliberately establish this condition and then rotate target email addresses, weakening source-level blocking. The authors should evaluate whether the successful-account threshold is susceptible to such manipulation, how long shared-IP status persists, and how it behaves under attacker-controlled account churn. The security–availability trade-off should be quantified across residential, enterprise-NAT, and proxy scenarios."*

> **Authors' Response:**  
> We thank the reviewer for raising this incisive and realistic threat scenario regarding the potential gaming of the shared-NAT relaxation threshold. We have performed a comprehensive empirical evaluation of the "NAT Gaming" attack vector and implemented structural guardrails that strictly bound an adversary's capability.
>
> **1. Structural Hardening: Shared-IP Abuse Circuit Breaker (Section 4.4):**  
> We enhanced `McpMaliciousLoginMiddleware.php` with an automated circuit breaker:
> - Even on an IP classified as shared, the system monitors distinct accounts blocked from that IP (`blocked:accounts:ip:$ip`).
> - If an attacker attempts to exploit the account-scoped relaxation by rotating victim accounts, the circuit breaker trips as soon as $\theta_{\text{abuse}} = 5$ distinct accounts are blocked (or aggregate IP attempts cross 25).
> - Upon tripping, the NAT relaxation is revoked, and the entire source IP is quarantined (`blocked:ip:$ip = 1`) for 900 seconds.
> - Furthermore, `LoginController.php` was hardened such that subsequent successful logins from the attacker's seeding accounts cannot unblock the IP while the circuit breaker remains tripped.
>
> **2. Empirical Evaluation of NAT Gaming (`nat_gaming` Scenario):**  
> In our evaluation harness, an adversary authenticates three valid seeding accounts on an IP and then attacks 10 victim accounts (3 attempts each, 30 attack attempts total):
> - **Native Laravel Throttling:** Failed completely ($0.00\%$ recall, 0/30 attacks caught) because per-tuple attempts remained below 5.
> - **Redis-Only:** Achieved $83.33\%$ recall but at the cost of $100\%$ collateral damage (blocking the entire IP indiscriminately after attempt 5, terminating all legitimate traffic behind an office NAT).
> - **HySA-MCP:** Achieved **$83.33\%$ recall** ($25/30$ intercepted, $0\%$ false positive rate) with **$0\%$ collateral damage** during initial exploration; the circuit breaker tripped on the 5th victim account, immediately quarantining the IP and terminating the remaining 15 attacks on the Redis fast-path in $\le 1\text{ ms}$.
>
> **3. Persistence and Account Churn Analysis:**  
> Shared-IP status persists strictly for the 900 s window TTL (`window_seconds = 900`). Under attacker-controlled account churn, the adversary must sacrifice fresh valid credentials every 15 minutes, but the circuit breaker guarantees that no more than five victim accounts can ever be probed before source-level quarantine applies.
>
> **4. Quantitative Trade-off Matrix (Table 9):**  
> Section 6 now incorporates **Table 9**, detailing the security–availability trade-off, collateral damage risk, and attacker cost across Residential, Enterprise-NAT, and Commercial Proxy topologies.
>
> *(Please refer to revised Section 4.4, Section 6.2, and Table 9 in the revised manuscript).*

---

---

We believe these revisions have substantially strengthened the manuscript's methodological rigour, and we thank the reviewer again for the insights that improved the work.

Sincerely,
The Authors