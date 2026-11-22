# System Architecture & Algorithm Flow
### Adaptive AI-Based Login Intrusion Detection (MCP + Rule Engine)

> **Source of truth (actual code):**
> - `app/Http/Middleware/McpMaliciousLoginMiddleware.php`
> - `app/Services/McpSecurityService.php`
> - `app/Http/Controllers/Auth/LoginController.php`
> - `app/Events/McpAttackDetected.php` / `app/Services/SecurityEmailService.php`

---

## Figure 1 — High-Level System Architecture

```mermaid
flowchart LR
    subgraph CL["Client"]
        U["User / Attacker<br/>(login attempt)"]
    end

    subgraph APP["Laravel Application (Pragati AI / VMS)"]
        MW["McpMaliciousLoginMiddleware<br/>Layer 1 — Rule Engine"]
        LC["LoginController<br/>Auth + counter update"]
        SVC["McpSecurityService<br/>Layer 2 — Hybrid scorer + MCP client"]
    end

    subgraph DB["Redis (15-min TTL)"]
        IPK["attack:login:ip:&lt;IP&gt;"]
        EK["attack:login:email:&lt;email&gt;"]
        BK["blocked:ip:&lt;IP&gt;"]
    end

    subgraph AI["MCP AI Engine (MiniMax)"]
        MM["POST /v1/chat/completions<br/>MiniMax-M2.1"]
    end

    subgraph NT["Notification"]
        EV["McpAttackDetected event<br/>→ email alert"]
    end

    U -->|"POST /login"| MW
    MW -->|"1. read counters"| IPK
    MW -->|"1. read counters"| EK
    MW -->|"2. threshold reached → analyzeLogin()"| SVC
    SVC -->|"3. context + risk score"| MM
    MM -->|"4. JSON response"| SVC
    SVC -->|"5. decision / action"| MW
    MW -->|"6a. block (1 hr)"| BK
    MW -->|"6b. block → alert"| EV
    MW -->|"6c. allow"| LC
    LC -->|"fail → incr"| IPK
    LC -->|"fail → incr"| EK
    LC -->|"success → reset"| IPK
    LC -->|"success → reset"| EK
    LC -->|"success → reset"| BK
```

---

## Figure 2 — Algorithm Flowchart (Detailed)

```mermaid
flowchart TD
    A["Login Request<br/>POST /login (email + password)"] --> B["McpMaliciousLoginMiddleware<br/>(pre-login AI layer)"]

    B --> C{"Email present?"}
    C -- "No" --> P["Proceed to LoginController"]
    C -- "Yes" --> D["Read Redis counters<br/>attack:login:ip / attack:login:email"]

    D --> E{"Threshold exceeded?<br/>ipAttempts &ge; 5 <b>OR</b> emailAttempts &ge; 3"}
    E -- "No" --> P

    E -- "Yes" --> F["McpSecurityService::analyzeLogin()"]

    F --> F1["Step 1 — getRiskScore()<br/>rule-based deterministic score"]
    F1 --> F2["Step 2 — build default response<br/>decision + action from score"]
    F2 --> F3["Step 3 — call MiniMax AI<br/>POST /v1/chat/completions"]

    F3 --> G{"AI response valid?"}
    G -- "Yes" --> G1["Parse JSON + merge with default<br/>override decision/action from risk_score"]
    G -- "No (exception / invalid JSON)" --> G2["FALLBACK<br/>use rule-based default<br/>(fail-open)"]

    G1 --> H["Return structured response<br/>decision, attack_type, risk_score, confidence, action"]
    G2 --> H

    H --> I{"decision == MALICIOUS<br/><b>OR</b> risk_score &ge; 80 ?"}

    I -- "Yes" --> J["BLOCK<br/>Redis setex blocked:ip (3600 s)<br/>fire McpAttackDetected → email alert"]
    J --> K["Return 403<br/>'Access blocked for 1 hour'"]

    I -- "No" --> P

    P --> L["LoginController::login()"]
    L --> M{"blocked:ip exists?"}
    M -- "Yes" --> MB["Reject<br/>'Access temporarily blocked'"]
    M -- "No" --> N["Auth::attempt(email, password)"]

    N --> O{"Credentials valid?"}
    O -- "No" --> Q["Increment Redis counters<br/>incr ip + incr email (expire 900 s)"]
    Q --> R["Return 'Invalid credentials'"]
    O -- "Yes" --> S["Reset counters + blocked key<br/>session regenerate"]
    S --> T["Redirect by role<br/>admin → /admin/dashboard<br/>else → /dashboard"]
```

---

## Figure 3 — Sequence Diagram (Request Lifecycle)

```mermaid
sequenceDiagram
    autonumber
    actor U as User/Attacker
    participant MW as McpMaliciousLoginMiddleware
    participant RD as Redis
    participant SVC as McpSecurityService
    participant AI as MiniMax MCP
    participant LC as LoginController

    U->>MW: POST /login (email, password)
    MW->>RD: GET attack:login:ip:<IP>, attack:login:email:<email>
    RD-->>MW: ipAttempts, emailAttempts

    alt threshold NOT exceeded
        MW->>LC: next() (allow)
    else threshold exceeded
        MW->>SVC: analyzeLogin({ip, email, ipAttempts, emailAttempts})
        SVC->>SVC: getRiskScore() (rule-based)
        SVC->>AI: POST /v1/chat/completions (context + score)

        alt AI success
            AI-->>SVC: JSON {decision, risk_score, ...}
            SVC->>SVC: override decision/action from risk_score
        else AI failure / invalid
            SVC->>SVC: FALLBACK → rule-based default (fail-open)
        end

        SVC-->>MW: {decision, attack_type, risk_score, confidence, action}

        alt MALICIOUS or risk_score >= 80
            MW->>RD: SETEX blocked:ip:<IP> (3600 s)
            MW-->>U: 403 Access blocked for 1 hour
        else SAFE / SUSPICIOUS
            MW->>LC: next() (allow)
        end
    end

    LC->>LC: Auth::attempt(email, password)

    alt auth fails
        LC->>RD: INCR ip + email (EXPIRE 900 s)
        LC-->>U: Invalid credentials
    else auth succeeds
        LC->>RD: DEL counters + blocked:ip
        LC-->>U: Redirect by role
    end
```

---

## Step-by-Step Explanation

1. **Interception** — `McpMaliciousLoginMiddleware` intercepts every `POST /login` **before** the controller runs. It extracts the client `IP` and submitted `email`.

2. **Redis counter read** — The middleware reads two counters (`attack:login:ip:<IP>`, `attack:login:email:<email>`). These counters hold the number of **prior failed attempts** (incremented by the controller on each failure, with a 15-minute TTL).

3. **Threshold gate (Rule Engine, Layer 1)** — MCP/AI analysis is invoked **only when** `ipAttempts >= 5` **OR** `emailAttempts >= 3`. Below the threshold, the request flows straight to the normal auth path (this keeps API cost low and avoids latency for ordinary users).

4. **Hybrid scoring (Layer 2)** — `McpSecurityService::analyzeLogin()`:
   - **(a)** computes a deterministic `risk_score` via `getRiskScore()`,
   - **(b)** builds a **default** response from that score,
   - **(c)** calls the MiniMax MCP model with the context (`ip`, `email`, `ip_attempts`, `email_attempts`, 15-min window),
   - **(d)** on success, merges the AI JSON with the default and **re-derives `decision` and `recommended_action` from the numeric `risk_score`** (the AI's own decision text is overridden).

5. **Fallback (fail-open)** — If the AI call throws an exception or returns invalid JSON, the system returns the **rule-based default** instead of crashing or blocking the user. This guarantees availability even when the MCP endpoint is down.

6. **Blocking decision** — The middleware blocks **only if** `decision == MALICIOUS` **OR** `risk_score >= 80`. On block: it writes `blocked:ip:<IP>` (1-hour TTL), fires `McpAttackDetected` (which triggers an email alert to the security admin), and returns HTTP `403`.

7. **Normal auth path** — `LoginController::login()` re-checks the `blocked:ip` key (defense-in-depth), then calls `Auth::attempt()`:
   - **fail** → increments both Redis counters (15-min TTL) and returns "Invalid credentials",
   - **success** → clears the counters and the block key, regenerates the session, and redirects by role.

---

## Decision / Action Reference (from code)

| Risk Score | Decision | attack_type | Recommended Action |
|---|---|---|---|
| 0 – 29 | `SAFE` | normal_user | `monitor` |
| 30 – 49 | `SAFE` | normal_user | `captcha` |
| 50 – 79 | `SUSPICIOUS` | credential_stuffing | `otp` |
| 80 – 100 | `MALICIOUS` | brute_force | `block_ip` |

> **Note for the paper:** the AI prompt only asks for a binary `SAFE`/`MALICIOUS`, but the system introduces a third tier `SUSPICIOUS` when overriding the decision from `risk_score` (50–79).

---

## How to export for the paper

Render the Mermaid diagrams to PNG/SVG with `mermaid-cli`:

```bash
npx @mermaid-js/mermaid-cli -i "System Architecture Algorithm Flow.md" -o figures/architecture.png
```

Each ```` ```mermaid ```` block can also be pasted into [mermaid.live](https://mermaid.live) and exported as SVG/PNG (300 DPI recommended for print).
