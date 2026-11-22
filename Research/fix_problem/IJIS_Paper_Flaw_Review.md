# Pre-Submission Review: A Hybrid AI-Driven Secure Authentication Framework

## Overall verdict

I reviewed the manuscript as if preparing a pre-submission review for the *International Journal of Information Security (IJIS)*.

**Current assessment: Major revision required — high risk of rejection if submitted as-is.**

The central problem is that the manuscript claims an experimentally validated, production-ready, AI-driven security framework, but the evidence presented is mostly a small simulated scenario plus unit tests of the authors' own implementation.

The paper has an interesting idea, but the main weaknesses are methodological validity, security design, experimental evidence, MCP novelty, and several internal inconsistencies.

---

# 1. The most serious problem: the "AI" contribution is not actually evaluated

The manuscript repeatedly presents the system as an MCP-integrated LLM decision-support system. The MCP layer collects contextual features, constructs a prompt, parses a SAFE/MALICIOUS response, and enforces decisions.

However, Algorithm 2 computes the main risk score using fixed, hand-crafted rules:

- IP attempts >= 10 -> 50 points
- IP attempts 5–9 -> 30
- IP attempts 3–4 -> 15
- email attempts >= 5 -> 40
- email attempts 3–4 -> 25
- final score = min(100, IP contribution + email contribution)

The final decisions are then mapped to fixed score thresholds.

### Why this is a serious problem

A reviewer can reasonably ask:

> What exactly is the LLM contributing?

The paper does not demonstrate that the LLM materially improves security performance compared with the rule-based system.

### What should be added

Add a proper ablation study comparing:

1. Laravel/native throttling
2. Redis-only
3. Rule-based scoring
4. LLM-only
5. MCP + LLM
6. Redis + rule-based
7. Full proposed hybrid

Report accuracy, precision, recall, F1, false-positive rate, false-negative rate, and latency.

---

# 2. The reported 97% accuracy is not convincing

The manuscript evaluates a simulated scenario of only 100 login attempts:

- 70 legitimate
- 30 malicious

It reports:

- Accuracy: 97%
- Precision: 93.5%
- Recall: 96.7%
- Specificity: 97.1%
- F1: 95.1%

The confusion matrix is:

- TN = 68
- FP = 2
- FN = 1
- TP = 29

The arithmetic is approximately correct, but the experimental design is insufficient for the claims being made.

### Missing information

The manuscript does not adequately explain:

- how legitimate attempts were generated;
- how malicious attempts were generated;
- how attack labels were assigned;
- how brute force was distinguished from credential stuffing;
- whether IP addresses were randomized;
- whether email addresses were randomized;
- whether attacks were distributed;
- whether NAT was simulated;
- whether timing distributions resembled real users;
- whether the LLM was given information that trivially revealed the label.

A 100-event hand-designed simulation is not sufficient to support production-level detection claims.

### Fix

Use a much larger, independently constructed dataset, preferably with thousands or tens of thousands of authentication events and clearly defined attack classes.

---

# 3. There is a serious circular-evaluation problem

The risk score determines the classification.

Algorithm 2 defines the scoring thresholds and labels. The evaluation then uses the same rule-based mechanism to generate the reported confusion matrix.

This creates a circular evaluation:

**You designed the rules -> generated scenarios -> applied the rules -> measured how well the rules classify those scenarios.**

This does not demonstrate generalization to unseen attacks.

### Fix

Use an independent test set:

- development/training scenarios -> choose parameters;
- validation scenarios -> tune parameters;
- completely unseen test scenarios -> final evaluation.

Ideally, use real-world or publicly reproducible authentication data where appropriate.

---

# 4. "100% correctness" from PHPUnit tests is misleading

The paper says the PHPUnit test suite confirmed "100% correctness."

That is not a defensible scientific claim.

35 tests passing means:

> All 35 programmed tests passed.

It does **not** establish:

- security effectiveness;
- generalization;
- robustness;
- adversarial resistance;
- false-positive performance;
- real-world accuracy.

### Fix

Replace claims such as "100% correctness" with:

> "All predefined unit tests passed."

---

# 5. The fail-open design is a major security vulnerability

The manuscript explicitly defines a fail-open design in which the system defaults to allowing access when critical security components are unavailable.

Algorithm 1 says that if the API call fails or returns an invalid response, the system retains the rule-based defaults and follows a fail-open design.

### Why this is dangerous

An attacker might intentionally trigger or exploit:

- MCP failure;
- LLM API timeout;
- DNS/network failure;
- provider outage;
- API quota exhaustion;
- request saturation.

If the fallback is permissive, an attacker may attempt to turn the security system into:

**attack -> cause AI failure -> bypass AI protection**

The paper currently does not analyze this attack surface.

### Better design

Consider risk-adaptive fallback behavior:

| Condition | Possible action |
|---|---|
| Low risk + AI unavailable | Allow + monitor |
| Medium risk + AI unavailable | CAPTCHA/OTP |
| High risk + AI unavailable | Block/step-up authentication |
| Redis unavailable | Emergency rate-limit policy |
| Repeated AI failures | Temporary degraded-security mode |

Then experimentally evaluate the security/availability trade-off.

---

# 6. The system does not really demonstrate "behavioral analysis"

The paper repeatedly uses terms such as "behavioral analysis" and "context-aware threat analysis."

But the implementation described primarily uses:

- IP address;
- email identifier;
- failed-attempt count;
- timestamp/time window.

The paper's definition of context-aware analysis mentions IP reputation, historical behavior, request patterns, and temporal characteristics, but the implementation does not demonstrate most of these features.

### Fix

Either reduce the claims or implement additional contextual features such as:

- device/browser fingerprint;
- ASN;
- IP reputation;
- User-Agent;
- login velocity;
- inter-arrival-time statistics;
- successful/failed login ratio;
- device novelty;
- proxy/VPN/Tor indicators.

---

# 7. MCP itself does not yet appear to provide a strong research contribution

The manuscript describes MCP as a custom protocol/service layer between the authentication system and the LLM.

The apparent flow is essentially:

**Laravel -> MCP/custom service -> prompt -> LLM API -> SAFE/MALICIOUS**

A reviewer may ask:

> Why is MCP necessary?

The manuscript needs to demonstrate the benefit of MCP itself rather than simply using MCP as an architectural label.

### Recommended experiment

Compare:

**Direct API**
Laravel -> MiniMax API

versus

**MCP**
Laravel -> MCP server -> model

Measure:

- latency;
- interoperability;
- security;
- modularity;
- model substitution;
- authorization;
- auditability;
- failure behavior;
- scalability.

Without this comparison, the MCP contribution is weakly demonstrated.

---

# 8. The MCP description needs to be updated and made precise

The manuscript needs to explicitly state:

1. Which MCP specification/version was implemented.
2. Which transport is used.
3. Whether the implementation conforms to the official MCP specification.
4. Which MCP primitives are actually used.
5. Whether the implementation is an MCP server/client or a custom MCP-like service.
6. How authentication and authorization are handled.
7. How the architecture relates to the current MCP security model.

Because MCP is evolving rapidly, a current submission needs precise protocol-level terminology and an explicit security discussion.

---

# 9. The claim of "production-ready" is unsupported

The abstract says the system is a "production-ready, cost-effective security solution."

The conclusion similarly states that it is suitable for real-world enterprise applications.

Given the current evaluation, those claims are too strong.

The current evidence consists mainly of:

- 35 PHPUnit tests;
- 100 simulated login attempts;
- a few API latency measurements;
- an estimated Redis memory footprint.

That is not enough to establish production readiness.

### Better wording

Use something like:

> "The results demonstrate potential for deployment in production-oriented web authentication environments."

Avoid "production-ready" until substantially stronger evidence is provided.

---

# 10. The Redis memory experiment is mostly an estimate, not a measured scalability experiment

The paper estimates approximately 1.1 MB for 10,000 concurrent IP addresses and 5,000 email addresses based on assumed memory per key.

This should be experimentally measured.

### Recommended experiment

Test:

- 1K entities
- 10K
- 100K
- 1M
- potentially 10M

Measure:

- Redis used memory;
- peak memory;
- fragmentation;
- operations/sec;
- latency;
- eviction behavior.

Then plot memory against tracked entities.

---

# 11. The "microsecond-level" claim is inconsistent with the reported latency

The abstract describes Redis counters as microsecond-level, but Table 5 reports an average Redis-only response time of approximately 8 ms.

You need to distinguish:

- Redis command latency;
- middleware latency;
- network latency;
- complete authentication-request latency.

If the Redis operation itself is microsecond-level but the entire request is 8 ms, state that explicitly.

---

# 12. The claimed sliding-window implementation appears inconsistent

The paper describes the mechanism as a "15-minute sliding window."

However, the shown Redis logic is essentially:

- INCR a key;
- EXPIRE the key after 900 seconds.

That is not a true sliding-window algorithm. It is closer to a counter with a TTL.

### Fix

Either:

- implement a true sliding window using timestamps/sorted sets;
- use an atomic Lua implementation;
- use token bucket/leaky bucket;
- or accurately call the current method a fixed/TTL counter.

For a security paper, the distinction matters.

---

# 13. Race conditions and atomicity are not discussed

The shown counter logic performs increment and expiration as separate operations.

For security-critical rate limiting, concurrency and atomicity matter.

Consider:

- Lua scripting;
- Redis transactions;
- atomic rate-limit primitives.

The paper should explain how concurrent login requests are handled safely.

---

# 14. IP-based blocking is vulnerable to NAT/shared networks

The system blocks:

`blocked:ip:{ip}`

for one hour.

This means a malicious user can potentially cause all users behind the same public IP to be blocked.

Examples include:

- universities;
- corporate networks;
- mobile carrier NAT;
- cafés;
- public Wi-Fi;
- residential NAT.

This can directly create false positives.

### Required evaluation

Test shared-IP environments with:

- many legitimate users;
- one attacker;
- varying attack rates.

Measure collateral blocking.

---

# 15. Email-based tracking creates account-lockout and evasion issues

The system tracks:

`attack:login:email:{email}`

An attacker can:

- rotate through thousands of email addresses;
- target a single victim email repeatedly;
- distribute attacks across IPs;
- distribute attacks across accounts.

This creates two opposite problems:

1. attackers can evade thresholds by rotating identifiers;
2. attackers can cause denial-of-service/account lockout against targeted users.

These attack modes should be evaluated.

---

# 16. The threat model is missing

A security paper needs a formal threat model.

### Attacker capabilities should specify whether an attacker can:

- rotate IP addresses;
- rotate accounts;
- know the thresholds;
- observe CAPTCHA/OTP responses;
- manipulate User-Agent;
- trigger MCP failures;
- exploit prompt injection;
- manipulate contextual inputs;
- replay requests;
- distribute attacks through botnets.

### Attacker goals should include:

- credential compromise;
- account enumeration;
- account lockout;
- rate-limit bypass;
- resource exhaustion;
- MCP/LLM exhaustion;
- evasion.

### Trusted components should also be defined

For example:

- Laravel;
- Redis;
- MCP server;
- LLM provider;
- network;
- administrators.

---

# 17. There is no adversarial evaluation

The current experiments mostly evaluate ordinary scenarios.

A security paper should test an attacker actively trying to bypass the system.

At minimum test:

### Attack A — IP rotation

Compare:

- one IP -> 100 attempts;
- 100 IPs -> one attempt each.

### Attack B — Email rotation

Compare:

- one email -> 100 attempts;
- 100 emails -> one attempt each.

### Attack C — Distributed credential stuffing

For example:

100 IPs x multiple credentials.

### Attack D — Low-and-slow attack

One attempt every few minutes.

### Attack E — Shared NAT

Many legitimate users plus one attacker from the same IP.

### Attack F — AI outage

Test multiple levels of LLM/MCP failure.

### Attack G — AI manipulation

Test whether malicious input can influence the LLM into returning SAFE.

---

# 18. Prompt injection is not addressed

The paper discusses prompt injection as an MCP-related threat in its related work, but does not adequately analyze it in its own architecture.

If user-controlled information enters the prompt, an attacker may attempt to influence the model's decision.

You need to discuss:

- separation of trusted instructions from untrusted data;
- input encoding;
- structured context;
- output validation;
- deterministic security overrides;
- model refusal/failure handling;
- prompt-injection testing.

---

# 19. Sending email/IP information to a third-party LLM creates privacy concerns

The system sends contextual information such as IP, email, attempt counts, and temporal information to the LLM service.

This raises questions about:

- personally identifiable information;
- provider logging;
- retention;
- cross-border transfer;
- organizational privacy requirements;
- data minimization.

### Possible improvement

Use pseudonymization where practical:

- hashed/pseudonymized email;
- IP prefix or salted hash;
- categorical reputation features instead of raw identifiers.

The paper should clearly state what data leaves the authentication server.

---

# 20. The baselines are too weak

The comparison is mainly:

- Traditional throttle;
- Redis only;
- proposed Redis + MCP.

A stronger evaluation should include:

- Laravel native rate limiting;
- Redis rate limiting;
- account-based throttling;
- IP + account throttling;
- CAPTCHA-based defense;
- adaptive authentication;
- conventional ML classifier;
- random forest/gradient boosting;
- anomaly detection;
- rule-based scoring without LLM.

Otherwise it is difficult to determine whether the LLM provides meaningful improvement.

---

# 21. Latency evaluation needs statistical treatment

Only a few individual MCP API calls are reported.

Three calls are insufficient to characterize a latency distribution.

Use at least 100 or 1000 calls where feasible and report:

- mean;
- median;
- standard deviation;
- p50;
- p95;
- p99;
- timeout rate;
- error rate.

For a security system, p95 and p99 are particularly important.

---

# 22. There is no serious load/stress test

The manuscript makes scalability claims without a proper stress evaluation.

Recommended loads:

- 10 requests/sec;
- 100 requests/sec;
- 1K requests/sec;
- 5K requests/sec;
- 10K requests/sec.

Measure:

- CPU;
- memory;
- Redis throughput;
- Laravel throughput;
- LLM request volume;
- latency;
- failed requests;
- Redis memory.

---

# 23. The 3–5 AI calls per 100 logins claim is not enough

The manuscript states that approximately 3–5 of every 100 login attempts invoke the AI.

This is primarily a consequence of the chosen thresholds, not an empirically established property of the system.

Measure AI invocation rates under:

- normal traffic;
- brute force;
- credential stuffing;
- password spraying;
- distributed attacks;
- mixed traffic.

---

# 24. Attack-type classification is not really demonstrated

The paper claims to distinguish attack types such as brute force and credential stuffing.

But Algorithm 2 simply maps score ranges to labels.

That is not a genuine attack-type classifier.

### Fix

Either:

**remove the attack-type classification claim**

or build and evaluate a real multi-class classifier using features that actually distinguish attack patterns.

---

# 25. The thresholds appear arbitrary

The paper uses thresholds such as:

- IP threshold = 5;
- email threshold = 3;
- block score = 80;
- OTP score = 50;
- CAPTCHA score = 30.

The manuscript does not explain how these values were selected.

### Required improvement

Provide a threshold-selection methodology based on:

- development data;
- validation data;
- ROC/PR analysis;
- expected security cost;
- false-positive/false-negative tradeoffs;
- or another defensible optimization method.

Then freeze the thresholds and evaluate on unseen test data.

---

# 26. "Low false positives" is not demonstrated strongly enough

There are only two false positives among 70 legitimate attempts.

That produces a small-sample estimate and has substantial uncertainty.

Report confidence intervals and avoid presenting the value as established production performance.

---

# 27. Related work needs to become more analytical

The related-work section is mostly organized as:

> Paper X did A, but it does not do B.

That is useful but insufficient for a strong journal paper.

A stronger structure would be:

### Category 1 — Rule-based authentication defense

Strengths and limitations.

### Category 2 — ML-based authentication

Strengths and limitations.

### Category 3 — LLM-based security

Strengths and limitations.

### Category 4 — MCP security

Strengths and limitations.

### Category 5 — Hybrid approaches

Strengths and limitations.

Then clearly identify the specific research gap that your experiments address.

---

# 28. The "no existing work" claim is too strong

The manuscript claims that no existing work has integrated MCP-enabled LLMs with real-time threshold monitoring for authentication security in production web environments.

Unless you conducted a systematic literature search, avoid "no existing work."

Use language such as:

> "To the best of our knowledge, we found limited prior work..."

or:

> "Our literature search identified no prior study that experimentally evaluates..."

and describe the search strategy.

---

# 29. References need substantial checking

Several references are blogs, arXiv papers, theses, or relatively weak venues.

Important claims about technology adoption and security should preferably be supported by:

- peer-reviewed research;
- official specifications/documentation;
- authoritative industry reports when appropriate.

References concerning MCP should also be checked against the current MCP specification and security model.

---

# 30. There are internal inconsistencies

### Inconsistency A — Sliding window

The text says sliding window, but the shown implementation is a TTL counter.

### Inconsistency B — AI decision vs rule decision

The manuscript describes the LLM as classifying requests, but the final decision is largely governed by deterministic risk-score thresholds.

### Inconsistency C — Fail-open security

The paper treats fail-open as a security advantage but does not analyze the security cost of allowing access when AI is unavailable.

### Inconsistency D — MCP's role

One part describes Redis as the first-line rate limiter and MCP as conditional analysis, while another description says MCP performs primary rate-limit analysis. These descriptions need to be reconciled.

---

# 31. The evaluation is too shallow for the breadth of the claims

The paper claims:

- AI security;
- MCP integration;
- explainability;
- authentication;
- scalability;
- production readiness;
- attack classification;
- low false positives;
- high accuracy;
- fault tolerance.

That is a very broad set of claims for the amount of evidence currently provided.

The solution is not necessarily to make the paper longer. Instead, make the contribution narrower and experimentally stronger.

---

# Likely reviewer assessment

| Criterion | Current assessment |
|---|---|
| Topic relevance | Good |
| Motivation | Good |
| Architecture idea | Interesting |
| Novelty | Unclear |
| MCP contribution | Weakly demonstrated |
| Algorithmic novelty | Limited |
| Threat model | Missing |
| Experimental design | Weak |
| Dataset | Missing/insufficient |
| Baselines | Insufficient |
| Ablation study | Missing |
| Statistical analysis | Missing |
| Security analysis | Insufficient |
| Adversarial evaluation | Missing |
| Scalability evaluation | Insufficient |
| Reproducibility | Insufficient |
| Claims vs evidence | Overstated |
| Writing | Generally understandable |
| Overall | **Major revision** |

---

# The 5 things to fix FIRST

## 1. Replace the 100-request simulated experiment

Create a genuine experimental dataset with thousands or tens of thousands of authentication events and clearly defined attack classes.

## 2. Add an ablation study

Compare:

**Redis -> Rule -> LLM -> MCP+LLM -> Proposed hybrid**

Without this, the contribution of MCP/LLM is not demonstrated.

## 3. Add an adversarial security evaluation

At minimum:

- distributed IP attacks;
- account rotation;
- password spraying;
- low-and-slow attacks;
- shared NAT;
- AI outage;
- attacker-controlled inputs;
- LLM manipulation/prompt injection.

## 4. Rework the fail-open mechanism

Do not simply claim fail-open is secure.

Analyze the availability/security trade-off and preferably implement risk-dependent fallback behavior.

## 5. Clarify exactly what "MCP integration" means

Explicitly specify:

- MCP version;
- MCP client;
- MCP server;
- transport;
- messages;
- authentication;
- authorization;
- security controls;
- why MCP is better than direct API integration.

---

# A much stronger experimental design

## Experiment 1 — Detection effectiveness

Use a dataset of at least 10,000–100,000 login events where practical.

Classes:

- legitimate;
- brute force;
- credential stuffing;
- password spraying;
- distributed brute force;
- low-and-slow.

Metrics:

- Accuracy;
- Precision;
- Recall;
- F1;
- FPR;
- FNR;
- ROC-AUC;
- PR-AUC.

---

## Experiment 2 — Ablation

Compare:

1. Laravel throttle
2. Redis only
3. Rule-based
4. LLM only
5. MCP + LLM
6. Redis + rule
7. Full proposed framework

This should become a central experiment.

---

## Experiment 3 — Robustness

Test:

- IP rotation;
- email rotation;
- NAT;
- distributed attacks;
- low-and-slow attacks;
- credential stuffing;
- password spraying.

---

## Experiment 4 — MCP/LLM failure

Test:

- normal API;
- 100 ms delay;
- 500 ms delay;
- 1 s delay;
- 3 s delay;
- timeout;
- malformed response;
- API unavailable;
- rate-limit response.

Measure:

- detection rate;
- false-positive rate;
- latency;
- availability.

---

## Experiment 5 — Scalability

Test:

**1K -> 10K -> 100K -> 1M tracked entities**

Measure:

- Redis memory;
- CPU;
- throughput;
- latency;
- p95/p99 latency.

---

## Experiment 6 — MCP security

Evaluate:

- prompt injection;
- malicious context;
- unauthorized MCP requests;
- replay;
- spoofed MCP client;
- API credential compromise;
- data leakage;
- model manipulation.

This would make the MCP component much more appropriate for an information-security journal.

---

# Recommended conceptual reframing

The current paper essentially argues:

> "LLM + MCP makes authentication better."

That is difficult to prove with the current architecture.

A stronger contribution would be:

> **"A risk-adaptive hybrid authentication architecture that uses deterministic security controls for fast-path enforcement and selectively invokes an LLM through MCP for contextual analysis, with independently evaluated fallback and adversarial-resilience mechanisms."**

This makes the **security architecture and evaluation**, rather than merely "using an LLM," the contribution.

---

# Bottom line

There is a potentially publishable idea here, but I would **not submit this version to IJIS yet**.

The biggest danger is that a reviewer can reduce the paper to:

> **"This is a manually designed IP/email threshold system with an LLM added on top, evaluated on 100 hand-crafted cases."**

The revision needs to make that criticism impossible to sustain.

The highest-priority improvements are:

**independent dataset + rigorous baselines + ablation + adversarial evaluation + real scalability testing + MCP conformance/novelty + corrected fail-open security model.**

Because MCP is evolving rapidly, the manuscript should also be updated against the current specification rather than describing MCP only generically.
