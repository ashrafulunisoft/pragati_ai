# Threat Model — MCP-Based AI Secure Login System (Pragati AI)

**Status:** Living document — update whenever the security architecture changes.
**Scope:** `McpMaliciousLoginMiddleware`, `McpSecurityService`, `McpClient`/`/api/mcp`, `SecurityStore`, `LoginController`.
**Related fixes:** Problems 1–6 (`FIXES_APPLIED.md`), evaluation harness (`php artisan security:evaluate`).

---

## 1. System under analysis

```
Browser/Attacker
   │ login POST /login
   ▼
McpMaliciousLoginMiddleware ── reads counters ──▶ SecurityStore (Redis, cache fallback)
   │ thresholds: ip≥5 or email≥3                       │ attack:login:ip:{ip} / :email:{email} (900s)
   ▼                                                    │ blocked:ip:{ip} (3600s) / mcp:degraded:ip:{ip}
McpSecurityService                                          │
   ├─ driver=direct → MiniMaxClient ──────────────▶ MiniMax API (third party)
   ├─ driver=mcp    → McpClient (JSON-RPC 2.0) ──▶ POST /api/mcp (Bearer token)
   │                                                    └─ AnalyzeLoginRiskTool → MiniMax API
   ├─ fusion: rules floor + AI verdict (F1..F5)
   └─ degraded policy: ≥80 fail-closed │ 50–79 emergency_throttle │ <50 fail-open-monitored
   ▼
LoginController (Laravel Auth, counters inc/reset, hard block check)
```

## 2. Assets

| Asset | Impact if compromised |
|---|---|
| User accounts (credentials) | Account takeover, insurance PII, claims/orders |
| Account availability | Legitimate users locked out (DoS) |
| Redis security state | Bypass or weaponisation of blocks |
| LLM API key / quota | Cost exhaustion, AI-layer outage |
| Login behaviour context (IP, email) | Privacy (PII leakage to third party) |
| MCP endpoint `/api/mcp` | Unauthorised security analysis oracle |

## 3. Attacker capabilities (assumed)

| # | Capability | Assumption |
|---|---|---|
| C1 | Guess credentials (online guessing) | Yes, unlimited attempts from own machines |
| C2 | Rotate source IPs (proxy/VPN/botnet) | Yes — many independent public IPs |
| C3 | Rotate target accounts | Yes — known/leaked email lists |
| C4 | Know all thresholds, weights and policies | Yes — assume the code/paper is public |
| C5 | Craft arbitrary `email` payloads (prompt injection) | Yes — free-text field before validation |
| C6 | Spoof `X-Forwarded-For` | Only if Laravel trusts proxies — `ip()` uses REMOTE_ADDR unless TrustProxies is enabled for that proxy; treat proxy header as untrusted on public interfaces |
| C7 | Withhold/induce AI-layer failures | Partially — can saturate or exploit third-party outages (they cannot disable it directly) |
| C8 | Keep rate just below thresholds (low-and-slow) | Yes |
| C9 | Share an IP with victims (NAT) or attack from a shared IP | Yes |
| C10 | Intercept traffic client↔server | No (TLS assumed) |
| C11 | Compromise the server/Redis/MiniMax account | No (out of scope; infrastructure trust boundary) |

## 4. Attacker goals

| # | Goal | Primary defence | Residual risk |
|---|---|---|---|
| G1 | Credential compromise | Counters + AI analysis + block | Low-and-slow below thresholds (C8) — accepted; MFA recommended |
| G2 | Account enumeration | Uniform "Invalid credentials" response | Difficult to fully remove timing side channels |
| G3 | Victim lockout (DoS one account) | Block is **IP-based**, not account-based; block requires ip≥10 **and** email≥5 evidence or an AI MALICIOUS verdict | An attacker from one IP hitting one victim with 5–9 failures triggers analysis but cannot hard-block alone (score < 80) |
| G4 | Collateral NAT lockout | Success logins reset the shared IP counter | Still possible while attacker keeps failing on shared IP — measured in `nat_shared` scenario |
| G5 | Rate-limit bypass via IP/email rotation | Per-identifier counters (both axes) | Distributed stuffing (30 IPs × 2 accounts) evades counters — **only** the AI escalation path (F2) catches it; measured in `distributed_stuffing` |
| G6 | Prompt injection → SAFE verdict | L1 sanitiser, L2 malformed-skip, L3 prompt fencing, L4 rule floor (rules cannot be talked down) | Worst case reduces detection to the rules-only baseline (`adversarial` eval mode) — bounded, never full bypass of ≥80 evidence |
| G7 | Induce AI outage → degraded mode abuse | P2 tiers: ≥80 fail-closed, 50–79 N-strike (3) emergency throttle → 900s block, <50 monitored; HTTP timeout 10s + 1 retry | Sustained outages reduce medium-band protection to throttling |
| G8 | Redis exhaustion/failure | TTL-bound keys; cache fallback (SecurityStore) | Cache fallback is per-node and slower — availability trade-off documented |
| G9 | PII harvesting via LLM provider | P6 pseudonymisation (hashed email, IP prefix+hash leave the server only) | Provider still sees coarse network locality + behaviour counts |
| G10 | Unauthorised use of `/api/mcp` | Bearer token (`MCP_SERVER_TOKEN`), JSON-RPC validation, no other methods | Token compromise = analysis oracle (rate-limit at proxy recommended) |

## 5. Trusted components

| Component | Trust assumption |
|---|---|
| Laravel app server | Trusted (runs the policy) |
| Redis | Trusted store; compromise = full security state loss |
| `SecurityStore` cache fallback | Trusted, weaker consistency (per-node) |
| MCP endpoint `/api/mcp` | Trusted only with valid Bearer token |
| MiniMax API / LLM | **Semi-trusted**: sees pseudonymised context only; its verdict is advisory — deterministic rules bound every decision (fusion F1–F5) |
| Email channel (alerts) | Trusted delivery to admins |
| Client / network | Untrusted; TLS terminates at server |

## 6. Security guarantees (post-fix, testable)

1. **Rule floor invariant:** no AI output can lower the effective risk score below the deterministic rule score (`McpDecisionFusionTest`).
2. **Bounded leniency:** the best an attacker-controlled LLM can achieve is SUSPICIOUS (step-up) in the 50–79 band — never a full allow on medium evidence (`McpPromptInjectionTest`).
3. **Fail-closed high band:** with the AI layer completely down, ip≥10 ∧ email≥5 evidence still blocks (`McpFailoverPolicyTest`).
4. **Degraded medium band:** ≥3 medium-risk analyses while AI is down → 900s block (idem).
5. **Injection containment:** crafted identifiers cannot close/forge the `<context>` fence (sanitiser + bracket neutralisation) and cannot flip a ≥80 verdict (`McpPromptInjectionTest`).
6. **Redis-down continuity:** counters/blocks survive to the cache fallback (`McpFailoverPolicyTest`).
7. **MCP conformance:** the `/api/mcp` endpoint speaks JSON-RPC 2.0 with initialize/tools/call and rejects unauthenticated calls (`McpProtocolTest`).

## 7. Known accepted limitations (measured, not hidden)

| Limitation | Evidence |
|---|---|
| Low-and-slow below thresholds is undetected | `low_and_slow` scenario: recall 0 in every mode |
| Distributed stuffing defeats counters; only AI escalation catches it | `distributed_stuffing`: off=0%, honest=90% recall |
| NAT collateral blocking possible while an attacker fails on a shared IP | `nat_shared` scenario (legit successes reset the shared counter) |
| Compromised/hostile AI reduces recall to the rules-only baseline | `adversarial` mode: 11% vs honest 84% |
| `captcha`/`otp` recommended actions are logged/alerted but not yet enforced in the login UI | code: only `block_ip` is enforced |
