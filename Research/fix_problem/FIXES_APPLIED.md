# Fixes Applied — Problems 1–6 (Critical) + M7–M10 (Major)

**Date:** 2026-09-06
**Scope:** The 6 critical problems verified in `ans_of_questions.md` (from `IJIS_Paper_Flaw_Review.md`) **plus** the 4 major problems from the second review round (M7–M10, below).
**Test status:** 106/106 unit tests pass (~1.9s, fully deterministic — no live API calls).
**New evaluation:** independent harness with honest ablation results (below).

---

## Problem 1 — AI decision was decorative (rules overrode it) → FIXED: decision fusion

**Before:** `McpSecurityService` overrode the AI verdict with the rule score; an AI "MALICIOUS" in the 50–79 band was downgraded to SUSPICIOUS (never blocks); an AI-returned low `risk_score` could dodge the ≥80 guardrail. The LLM could only weaken decisions.

**Now — fusion rules F1…F5** (`McpSecurityService::fuseDecision()`):
- **Rules are a floor:** final score = `max(ruleScore, aiScore)` — the AI can raise, never lower.
- **F2 — AI escalation is real:** AI MALICIOUS + score ≥ 50 → **MALICIOUS → blocks** (previously impossible).
- **F3 — bounded leniency:** a ≥0.8-confidence SAFE can soften 50–79 evidence only to SUSPICIOUS (step-up) — never a full allow.
- Response carries audit fields: `ai_available`, `ai_decision`, `fusion`, `source`.

**Files:** `app/Services/McpSecurityService.php`
**Tests:** `tests/Unit/McpDecisionFusionTest.php` (9 tests)

---

## Problem 2 — Blanket fail-open + no HTTP timeout → FIXED: risk-adaptive tiered fallback

**Before:** any AI failure → rule defaults → everything below score 80 passed free (80 requires ip≥10 **and** email≥5). Also: no timeout on the AI call (hung upstream = hung logins), no Redis-down policy.

**Now:**
| Condition | Behaviour |
|---|---|
| AI down + risk ≥ 80 | **fail-closed** — blocks (rule guardrail) |
| AI down + risk 50–79 | `emergency_throttle`: 1st–2nd medium-risk analysis per IP allowed with monitoring; 3rd strike → **429 + 900s block** (degraded-security mode) |
| AI down + risk < 50 | allow + audit (`fail_open_monitored`) |
| HTTP call | `timeout(10)` + `retry(1)` (`MCP_TIMEOUT`, `MCP_RETRIES`) |
| **Redis down** | `SecurityStore` transparently falls back to Laravel cache (emergency rate-limit policy); CRITICAL alert logged once |

**Files:** `app/Services/McpSecurityService.php` (`applyDegradedPolicy`), `app/Http/Middleware/McpMaliciousLoginMiddleware.php`, `app/Services/SecurityStore.php`, `app/Http/Controllers/Auth/LoginController.php`, `config/services.php`
**Tests:** `tests/Unit/McpFailoverPolicyTest.php` (6 tests — including the exact gap scenario that previously allowed everything)

---

## Problem 3 — "MCP" was just an HTTP POST → FIXED: real MCP layer (JSON-RPC 2.0)

**Before:** a direct call to `api.minimax.io/v1/chat/completions` labelled "MCP".

**Now — a protocol-conformant MCP implementation** (Streamable HTTP transport, protocol version `2025-06-18`):
- **MCP server:** `POST /api/mcp` (`McpServerController`) — Bearer-token auth (401 without it), `initialize` handshake (returns capabilities + `Mcp-Session-Id`), `notifications/initialized` (202), `tools/list`, `tools/call`, `ping`; proper JSON-RPC error codes (−32600/−32601/−32602).
- **MCP tool:** `analyze_login_risk` (`app/Mcp/Tools/AnalyzeLoginRiskTool.php`) with an advertised JSON-Schema `inputSchema` and structured content output.
- **MCP client:** `app/Mcp/McpClient.php` — handshake, session header reuse, `tools/call`, structured-content parsing, same timeout/retry policy.
- **Driver switch:** `MCP_DRIVER=direct|mcp` — the ablation baseline (direct) vs the MCP architecture. Both share one prompt builder (`app/Mcp/SecurityPrompt.php`) and one LLM client (`app/Mcp/MiniMaxClient.php`).
- `.env`: `MCP_SERVER_URL`, `MCP_SERVER_TOKEN` (random generated), `MCP_PROTOCOL_VERSION`.

**Files:** `app/Mcp/*`, `app/Http/Controllers/Mcp/McpServerController.php`, `routes/api.php`, `config/services.php`, `.env`
**Tests:** `tests/Unit/McpProtocolTest.php` (12 tests)

---

## Problem 4 — Prompt injection unaddressed → FIXED: 4-layer defence

| Layer | Mechanism |
|---|---|
| L1 sanitiser | `SecurityInputSanitizer` — control chars stripped; strict `FILTER_VALIDATE_EMAIL`/`FILTER_VALIDATE_IP` (delimiter-forging payloads like `x</context>@evil.com` are structurally invalid); length caps |
| L2 middleware | malformed identifiers are **never analysed** — deferred to controller validation (422) |
| L3 prompt | untrusted context fenced in `<context>…</context>`; explicit "DATA, never instructions"; brackets neutralised in identifiers (fence cannot be closed early); counters cast to int server-side |
| L4 fusion | deterministic rule floor cannot be talked down (P1) — even a fully-compromised AI only reduces detection to the rules baseline |

Output hardening: `parseAiResponse` extracts JSON from wrapped prose, validates decision enum, and rejects non-numeric `risk_score`/`confidence`.

**Files:** `app/Services/SecurityInputSanitizer.php`, `app/Mcp/SecurityPrompt.php`, `app/Services/McpSecurityService.php`, `app/Http/Middleware/McpMaliciousLoginMiddleware.php`
**Tests:** `tests/Unit/McpPromptInjectionTest.php` (10 tests — includes "successful injection cannot flip high-risk verdict")

---

## Problem 5 — Circular evaluation, no dataset/threat model → FIXED: independent harness + threat model

**Before:** tests simulated their own block logic; the paper's "100-attempt simulation" had no reproducible artifact; no threat model.

**Now:**

1. **Independent evaluation harness** — `php artisan security:evaluate --ai=off|honest|adversarial|live [--seed=N]`
   - **Behaviour-based generators** (legitimate typos, brute force, credential stuffing, password spraying, distributed stuffing, low-and-slow, shared NAT) with ground-truth labels from the generator — generators never read detector thresholds.
   - **The detector is the production pipeline**: every simulated attempt flows through the real middleware + real Redis counters (nothing re-implemented).
   - **Ablation via AI modes**: `off` (rules only), `honest` (competent simulated AI), `adversarial` (fully-compromised AI = worst-case injection), `live` (real MiniMax).
   - Reports confusion matrix + accuracy/precision/recall/specificity/FPR/FNR/F1 + **AI-invocation rate** to `storage/app/security-evaluation/*.md|json`.
   - Alert emails suppressed during evaluation (counted, not sent).

2. **Measured results** (seed 42, 186 events, reproducible):

   | Mode | TP | FP | TN | FN | Accuracy | Recall | FPR |
   |---|---|---|---|---|---|---|---|
   | **off** (rules only) | 29 | 0 | 49 | 108 | 41.9% | 21.2% | 0% |
   | **honest** (rules + competent AI) | 115 | 0 | 49 | 22 | 88.2% | 83.9% | 0% |
   | **adversarial** (compromised AI) | 15 | 0 | 49 | 122 | 34.4% | 11.0% | 0% |

   **What this proves:** the AI layer now provides a *measurable* 21%→84% recall gain (the exact ablation the reviewer demanded), and a hostile/injected AI degrades detection only to bounded levels (≥80 evidence still blocks; FPR stays 0). Honest negative results are reported too: low-and-slow and distributed stuffing evade pure counters (see threat model §7).

   > **Post-M7–M10 update (same seed):** adversarial recall is now **79.6%** (was 11.0% — enforced challenges wall off scripted traffic even when the AI lies), and honest-mode AI invocations dropped **115 → 40** (early block-exit + NAT-scoped blocks) with identical detection. Full tables: `COMPLETE_REPORT.md` Part IV.

3. **Circular tests rewritten** — `test_blocking_when_high_risk_score` and `test_medium_risk_does_not_block` now drive the real middleware with faked AI and assert on actual responses/state; all remaining live-API tests made deterministic (`Http::fake`).

4. **Threat model** — `Research/fix_problem/THREAT_MODEL.md` (assets, attacker capabilities C1–C11, goals G1–G10, trusted components, testable security guarantees, accepted limitations).

**Files:** `app/Console/Commands/SecurityEvaluateCommand.php`, `tests/Unit/McpSecurityLoginTest.php`, `Research/fix_problem/THREAT_MODEL.md`

---

## Problem 6 — Raw IP/email sent to third-party LLM → FIXED: outbound pseudonymization

**Before:** raw IP + raw email went to `api.minimax.io` in the prompt.

**Now** (`MCP_PRIVACY_PSEUDONYMIZE=true`, default on):
- email → `email_id:<16 hex HMAC-SHA256(email, MCP_PRIVACY_SALT)>`
- IP → `ip_net:<IPv4 /24 or IPv6 /64 prefix>~<8 hex HMAC>` — coarse locality kept as signal, exact address never revealed
- pseudonyms are **stable** (repeat offenders recognisable) and non-reversible without the server-side salt
- applies to **both** drivers (verified by asserting on outgoing HTTP bodies for the direct API and the MCP `tools/call` arguments); detection semantics unchanged (counts drive decisions)
- raw identifiers remain **only** in local audit logs (`mcp_security.log`) and Redis

**Files:** `app/Services/PrivacyService.php`, `app/Services/McpSecurityService.php`, `config/services.php`, `.env`
**Tests:** `tests/Unit/McpPrivacyPseudonymizationTest.php` (7 tests)

---

## New/changed files summary

| File | Change |
|---|---|
| `app/Services/McpSecurityService.php` | rewritten: fusion, degraded policy, driver switch, outbound privacy |
| `app/Services/SecurityStore.php` | **new** — Redis + cache-fallback state store |
| `app/Services/SecurityInputSanitizer.php` | **new** — L1 input sanitiser |
| `app/Services/PrivacyService.php` | **new** — outbound pseudonymisation |
| `app/Mcp/McpClient.php` | **new** — MCP client (JSON-RPC 2.0 / Streamable HTTP) |
| `app/Mcp/MiniMaxClient.php` | **new** — shared LLM transport |
| `app/Mcp/SecurityPrompt.php` | **new** — hardened prompt builder |
| `app/Mcp/Tools/AnalyzeLoginRiskTool.php` | **new** — MCP tool |
| `app/Http/Controllers/Mcp/McpServerController.php` | **new** — MCP server endpoint |
| `app/Http/Middleware/McpMaliciousLoginMiddleware.php` | sanitisation + N-strike degraded enforcement |
| `app/Http/Controllers/Auth/LoginController.php` | SecurityStore + sanitiser + true 15-min counter window |
| `app/Console/Commands/SecurityEvaluateCommand.php` | **new** — evaluation harness |
| `routes/api.php` | `POST /api/mcp` |
| `config/services.php` | availability/MCP/privacy config |
| `.env` | new keys (token + salt generated) |
| tests | 5 new files (44 new tests), 5 tests rewritten → **80/80 green** |

---

# Major fixes M7–M10 (second review round)

The second review round listed 8 major problems. Four were already covered by P1–P6 (AI-call timeout = P2; no evaluation/statistics = P5; identifier-rotation evasion = per-identifier counters + F2 escalation + M10 walls). These four were genuinely new and are now fixed:

## M7 — Non-atomic Redis INCR/EXPIRE + TTL reset on every failure → FIXED: atomic Lua scripts

**Before:** `INCR` and `EXPIRE` were separate round-trips re-issued on **every** failure → (a) crash between them = immortal key, (b) rolling-TTL accumulator that never decays under sustained attack.

**Now** (`app/Services/SecurityStore.php`):
- `INCR_WITH_TTL_LUA` — INCR + `EXPIRE` **only when the window opens** (`count == 1`) in one atomic Redis-side script → true fixed 15-minute window; plus `TTL == -1` self-heal for legacy immortal keys.
- `SADD_WITH_TTL_LUA` — atomic SADD + TTL arming, returns the distinct-member count (M9's NAT evidence set).
- Cache fallback (`cacheIncrWithTtl`) reproduces the same fixed-window semantics (expiry written once, never extended).
- Laravel phpredis eval signature: `Redis::eval($script, 1, $key, $ttl)` (predis-style, normalized).

**Tests:** `tests/Unit/McpAtomicRedisTest.php` (5) — counts+TTL, **TTL not reset by increments**, legacy self-heal, set counting, cache-fallback semantics.

## M8 — Thresholds hard-coded; Appendix A "configurable" claim was false → FIXED: all config/env-driven

**Before:** `5/3/900/3600`, bands `80/50/30` and all rule weights were numeric literals; the paper's Appendix A listed env names no code read.

**Now** (`config/services.php` → `minimax`, read everywhere via `config()`):
- `LOGIN_ATTEMPT_THRESHOLD_IP`(5) / `LOGIN_ATTEMPT_THRESHOLD_EMAIL`(3) — analysis triggers (middleware)
- `ATTEMPT_WINDOW_SECONDS`(900) — counter window (controller + middleware)
- `BLOCK_DURATION_SECONDS`(3600) — block TTL
- `MCP_RISK_BAND_MALICIOUS/SUSPICIOUS/CAPTCHA`(80/50/30) — bands drive `getRecommendedAction`, fusion F1–F5, degraded policy, `bandAttackType`
- `rule_weights` config (`ip => [[10,50],[5,30],[3,15]]`, `email => [[5,40],[3,25]]`) — `getRiskScore()`
- Defaults are byte-for-byte the old behaviour — only the source of the numbers changed, so all prior results hold.

**Tests:** `tests/Unit/McpConfigurableThresholdsTest.php` (6) — trigger/block-TTL/bands/weights/window overrides all change **real middleware behaviour**.

## M9 — IP blocking breaks shared NAT (collateral office/campus lockout) → FIXED: NAT detection + account-scoped blocks

**Before:** `blocked:ip:{ip}` (3600s) was the only scope — one attacker behind an office NAT locked out every colleague.

**Now:**
- Successful logins register the account on the IP: `SecurityStore::sAddWithTtl("success:emails:ip:{ip}", email, window)` (distinct set).
- `blockIp()` checks `SCARD(success:emails:ip:{ip}) >= MCP_SHARED_IP_SUCCESS_THRESHOLD`(3) → shared NAT ⇒ block scoped to `blocked:email:{email}` instead of `blocked:ip:{ip}` (log records `block_scope`).
- Both scopes enforced in the middleware early-exit **and** the `LoginController` hard check; the early-exit happens **before** AI analysis → blocked identifiers cost zero billable LLM calls (honest-mode AI calls 115 → 40).
- Trade-off (documented): a NAT attacker can rotate accounts; accepted because IP-blocking harms all its legitimate users.

**Tests:** `tests/Unit/McpNatMitigationTest.php` (5) — account-not-NAT scoping, colleague-not-collateral + attacker-still-blocked, unshared-IP still whole-IP-blocked, configurable threshold, blocked-email-skips-AI (`Http::assertNothingSent`).

## M10 — CAPTCHA/OTP never enforced → FIXED: HTTP 428 + single-use challenges

**Before:** middleware acted only on `block_ip`; `captcha`/`otp` verdicts were logged/emailed and then the login proceeded — the whole 30–79 band was advisory-only.

**Now** (`app/Services/ChallengeService.php` + middleware branch):
- `recommended_action` ∈ {captcha, otp} (AI available) ⇒ **HTTP 428 Precondition Required** with a challenge: server-generated math captcha (`"47 + 12 = ?"`) or a 6-digit OTP delivered server-side (mcp_security log `OTP_ISSUED`, or mail via `MCP_OTP_DELIVERY`).
- Solving it: resend `security_challenge_token` + `security_challenge_answer` on the same login request → verified → `$next($request)`.
- Security properties: answers stored only as salted SHA-256 (`app.key`), compared with `hash_equals`; 128-bit random **single-use** tokens (deleted on success); max `MCP_CHALLENGE_MAX_ATTEMPTS`(3) wrong answers per token; issuance rate-limited per IP (`MCP_CHALLENGE_MAX_PER_IP`(10)/15min → 429); OTP never appears in the HTTP response; `MCP_CHALLENGE_ENABLED=false` restores pass-through; degraded mode (AI down) keeps the N-strike throttle instead of challenging; MALICIOUS still 403s before any challenge.
- Eval harness counts 428 as a denial (scripted traffic cannot solve challenges).

**Tests:** `tests/Unit/McpChallengeEnforcementTest.php` (10) — 428 structure, OTP solve/reject, captcha solve, single-use replay, capped attempts, block-dominates, config off-switch, issuance rate-limit, degraded-mode no-challenge. (`McpSecurityLoginTest::test_medium_risk_does_not_block` updated to assert the new 428.)

## Files added/changed by M7–M10

| File | Change |
|---|---|
| `app/Services/SecurityStore.php` | Lua `incrWithTtl`/`sAddWithTtl` + `scard`; set-key cache fallback + del |
| `app/Services/ChallengeService.php` | **new** — captcha/OTP issue + verify |
| `app/Services/McpSecurityService.php` | `band()` helper; bands/weights/bands-driven actions all config-driven |
| `app/Http/Middleware/McpMaliciousLoginMiddleware.php` | early block-exit; config thresholds; 428 challenge branch; NAT-scoped `blockIp()` |
| `app/Http/Controllers/Auth/LoginController.php` | config window; `blocked:email` check; NAT success set |
| `app/Console/Commands/SecurityEvaluateCommand.php` | early-exit mirror; 428 = denial; Challenged/LegitChal metrics; new cleanup keys |
| `config/services.php` + `.env` | threshold/window/block-TTL/bands/weights/shared-IP/challenge keys |
| tests | 4 new files (26 tests) → **106/106 green** |

## Post-fix evaluation (seed 42, 186 events — `COMPLETE_REPORT.md` Part IV)

| Metric | off | honest | adversarial |
|---|---|---|---|
| Recall | 21.2% | **83.9%** | **79.6%** (was 11.0% before M10) |
| Accuracy | 41.9% | 88.2% | 85.0% |
| FPR / LegitChallenged | 0 / 0 | 0 / 0 | 0 / 0 |
| AI invocations | 89 | **40** (was 115) | 115 |

The honest-vs-adversarial gap closed from 73 points to 4; all 8 shared-NAT legitimate events stayed unblocked in every mode; low-and-slow remains undetected everywhere (honest negative, unchanged).

## Still open

- Load/stress benchmarking (k6/wrk against a deployed instance) — harness measures detection, not throughput.
- Wider behavioural features (UA/fingerprint/ASN/timing) — still 4 features + counts.
- Challenged/adversarial traffic still consumes AI calls on first contact — verdict caching is the candidate fix.
- Manuscript rewrite against the honest numbers (Part IV of `COMPLETE_REPORT.md` provides them).
