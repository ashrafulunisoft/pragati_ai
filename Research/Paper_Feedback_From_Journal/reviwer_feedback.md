# Reviewer Feedback — Overall Assessment & Technical Comments

> 📌 **নোট (2026-11-19):** এই ফাইলটি আগে ফাঁকা ছিল; পোর্টাল থেকে পাওয়া রিভিউয়ারের মূল কমেন্ট `reviewer_attackment.md`-এ ছিল। সেগুলোই এখানে সংরক্ষণ করা হলো (অক্ষরে অক্ষরে, ইংরেজিতে — জার্নালের মূল ভাষায়)। বাংলা বিশ্লেষণ ও সমাধান-প্ল্যান: `fix_problem_form_journal_feedback_oneByone/glm/` ফোল্ডারে।

---

**Overall assessment**

The manuscript presents a practical hybrid authentication architecture that combines Redis-based telemetry, deterministic risk scoring, and selective LLM analysis through MCP. The attention to degraded-mode behavior, pseudonymization, and adversarial testing is valuable. However, several central claims require stronger experimental support, and the published algorithm needs clarification to establish consistency between risk scoring, challenge enforcement, and reported outcomes. The following comments identify the principal issues that should be addressed before the framework's effectiveness and production-readiness can be assessed reliably.

**Recommendation: Major revision**

**Technical comments**

**1. Validation of LLM-driven detection gains** *(Section 5.4; Table 6; Appendix A)*
The increase in recall from 21.2% (rules only) to 83.9% (rules + AI) is derived from a seeded evaluation whose 'honest' AI mode is described as a competent simulated AI. Consequently, the reported gain does not yet establish detection effectiveness of the deployed MiniMax-M2.1 integration. The authors should repeat the evaluation using live model responses, disclose the prompt and response schema, and report variation across repeated runs and representative attack distributions. Confusion matrices for live-model outcomes should be distinguished from simulation results.

**2. Detection gap under joint IP and account rotation** *(Section 4.1; Algorithm 1; Section 6; Table 7)*
Algorithm 1 bypasses AI analysis when both the IP and email attempt counters remain below their invocation thresholds. An adversary can exploit this gate by rotating source addresses and targeted accounts jointly, which is especially relevant to distributed credential stuffing and password spraying. The reported 90% recall for distributed stuffing therefore requires a clearer description of how the simulated events cross the escalation threshold. The authors should evaluate joint IP/account rotation, including a deliberately below-threshold adversary, and report the fraction of attacks for which the LLM is never invoked.

**3. Risk-score fusion and challenge-enforcement consistency** *(Section 4.3; Algorithms 1–2; Table 2)*
Section 4.3 states that the effective score is S = max(S_rule, S_AI), yet Algorithm 1 does not explicitly compute or use this fused score. In addition, the algorithm assigns token_428 to SUSPICIOUS requests but subsequently returns Allow in its terminal non-MALICIOUS branch. This pseudocode is difficult to reconcile with the claimed enforced HTTP 428 step-up mechanism. Please provide an unambiguous executable decision sequence (or equivalent state-transition specification), explain how S_AI is obtained and bounded, and verify that every SUSPICIOUS outcome consistently triggers the intended challenge.

**4. Semantics and timing of Redis failure counters** *(Sections 4.1–4.2; Listing 1; Figure 1)*
The risk score is defined using failed login attempts, but Listing 1 increments both Redis counters unconditionally and the interception middleware is positioned ahead of Laravel credential verification. It is therefore unclear whether the counters represent all login requests or confirmed failures, and when successful logins affect their values. This distinction materially changes risk scores and false-positive behavior. The authors should document the exact update sequence for success, failure, challenge, and blocked requests and add tests for legitimate users who repeatedly authenticate successfully.

**5. Separating threat detection from challenge-based prevention** *(Section 5.4; Table 6; Appendix A)*
The evaluation counts an unsolved HTTP 428 challenge as a 'denied' malicious event and includes it in the true-positive totals. Blocking an attack through a challenge is operationally useful, but it does not necessarily indicate that the classifier correctly identified malicious behavior. Please report detection/classification performance separately from enforcement outcomes, including explicit blocks, throttles, challenges, challenge completions, and successful unauthorized attempts. The zero observed false-positive rate is based on only 49 legitimate negatives; its uncertainty and sensitivity to legitimate users encountering challenges should be emphasized.

**6. Controlled baseline comparison and MCP-specific ablation** *(Section 5.3; Table 5)*
Table 5 places the proposed method alongside traditional Laravel throttling and Redis-only protection, but the paper acknowledges that the baseline latency values are indicative rather than measurements under the same harness. This prevents a controlled comparison of security effectiveness and overhead. The authors should evaluate all approaches on identical traffic traces and compute comparable recall, false-positive rate, throughput, and latency statistics.

**7. End-to-end latency and adversarial resource exhaustion** *(Sections 5.3, 5.6, and 6; Tables 3 and 5)*
The measured AI-call latency (approximately 2.6–4.4 seconds) is substantial for an interactive login path, while the reported 8 ms figure covers only the isolated Redis fast path. The authors should benchmark end-to-end login latency under concurrency, reporting p50/p95/p99, throughput, timeout rates, retry amplification, and behavior under upstream-model outages.

**8. Adversarial manipulation of NAT-aware blocking** *(Section 4.4; Algorithm 1; Sections 6–7)*
The NAT-aware defense switches from IP-scoped to email-scoped blocking when at least three distinct accounts have authenticated successfully from an IP address. An adversary with access to multiple valid accounts might deliberately establish this condition and then rotate target email addresses, weakening source-level blocking. The authors should evaluate whether the successful-account threshold is susceptible to such manipulation, how long shared-IP status persists, and how it behaves under attacker-controlled account churn. The security–availability trade-off should be quantified across residential, enterprise-NAT, and proxy scenarios.

**Closing note**

Addressing these points would substantially strengthen the manuscript's methodological reproducibility, the interpretation of its measured security outcomes, and the evidence supporting deployment in latency-sensitive authentication systems.

---

## সমাধানের অবস্থা (২০২৬-১১-১৯ অনুযায়ী)

| # | কমেন্ট | স্ট্যাটাস | সমাধান-ডকুমেন্ট |
|---|---|---|---|
| ১ | লাইভ LLM ভ্যালিডেশন | ✅ সম্পন্ন (Gemini live, ৫-seed, recall 70.79% ± 8.77%) | `glm/problem solve/update research paper.md` Entry 01 |
| ২ | Joint rotation gap | ✅ সম্পন্ন (below_threshold: recall 0%, No-AI 100%) | Entry 02 |
| ৩ | Fusion/428 consistency | ✅ সম্পন্ন (Algorithm রিরাইট টেক্সট ready) | Entry 03 |
| ৪ | Counter semantics | ✅ সম্পন্ন (update-sequence টেবিল + ৪ টেস্ট) | Entry 04 |
| ৫ | Detection vs enforcement | ✅ সম্পন্ন (Table 6a/6b + Wilson CI) | Entry 05 |
| ৬ | Baseline comparison | ✅ সম্পন্ন (৪ engine, একই ট্রেস + MCP ablation) | Entry 06 |
| ৭ | Latency benchmark | ✅ সম্পন্ন (৫-পাথ benchmark, p50/p95/p99) | Entry 07 |
| ৮ | NAT manipulation | ✅ সম্পন্ন (nat_gaming + circuit breaker) | Entry 08 |

**বাকি কাজ:** ম্যানুস্ক্রিপ্টে পরিবর্তন বসানো + হাইলাইট, রেসপন্স লেটার ফাইনাল, সাবমিশন।
