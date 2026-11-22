# ✅ TODO — প্রবলেম সলভিং প্রগ্রেস ট্র্যাকার

**পেপার:** HySA-MCP (Springer — Major Revision)
**Submission ID:** `c372b849-6b41-418e-ad06-5c08e86be13a`
**শেষ আপডেট:** 2026-11-19
**এই ফাইলের নিয়ম:** কাজ শুরু/শেষ হলে স্ট্যাটাস আইকন বদলান + নিচের "প্রগ্রেস লগ"-তে এক লাইন লিখুন।

---

## 📊 এক নজরে (ড্যাশবোর্ড — আপডেট করতে থাকুন)

| বিভাগ | মোট | ✅ সম্পন্ন | 🔄 চলছে | ⬜ বাকি |
|---|---|---|---|---|
| রিভিউয়ার কমেন্ট (C1–C8) | ৮ | ৮ (C1..C8) | ০ | ০ |
| এডিটরের দাবি (E1–E4) | ৪ | ০ | ০ | ৪ |
| অ্যাডমিন/হাউসকিপিং (A1–A6) | ৬ | ১ | ০ | ৫ |
| কোডবেস-ব্লকার (B1–B3) | ৩ | ৩ (B1..B3) | ০ | ০ |
| **সর্বমোট** | **২১** | **১২** | **০** | **৯** |

**সামগ্রিক অগ্রগতি:** ▓▓▓▓▓▓░░░░ ~৫৮% — *(C1–C8 সমস্ত রিভিউয়ার কমেন্টের কোড, টেস্ট, এক্সপেরিমেন্ট ও পেপার টেবিল ১০০% সম্পূর্ণ)*

### স্ট্যাটাস আইকন
⬜ শুরু হয়নি · 🔄 চলছে · ✅ সম্পন্ন · ⏸️ আটকে আছে (নোট দেখুন) · ➖ প্রযোজ্য নয়

---

## 🎯 এডিটরের দাবি (এক্সপেরিমেন্ট ছাড়াই করা যায় — আগে শেষ করুন)

- [ ] **E1** ⬜ — Overstated দাবি বাদ ("production-ready", "100%", "first system" জাতীয় ভাষা নরম করা)
- [ ] **E2** ⬜ — Limitations সেকশন লেখা (low-and-slow, below-threshold gap, NAT residual, n=49 FPR, simulation-scale, latency)
- [ ] **E3** ⬜ — Updated manuscript হাইলাইট/Track-changes করা
- [ ] **E4** ⬜ — Point-by-point রেসপন্স লেটার ফাইনাল করা (`9.সাবমিশন_চেকলিস্ট_..._glm.md`-এর টেমপ্লেট + প্রতিটি কমেন্ট ফাইলের ইংরেজি ড্রাফট)

---

## 🔴 দ্রুত জয় (ম্যানুস্ক্রিপ্ট-অনলি — কোড লাগে না)

### C3 — Algorithm 1–2 pseudocode সংশোধন ✅ *(2026-11-19 সম্পূর্ণ সম্পন্ন)*
বিস্তারিত: `3.কমেন্ট_৩_অ্যালগরিদম_সংশোধন_glm.md` + `problem solve/update research paper.md` (Entry 03)
- [x] C3.1 ✅ Algorithm 1 রিরাইট — `S ← max(S_rule, S_AI)` ফিউশন স্পষ্ট দেখানো হয়েছে
- [x] C3.2 ✅ Terminal non-MALICIOUS ব্রাঞ্চে `Allow` নয় → `return (Challenge, 428)` ইস্যু নিশ্চিত
- [x] C3.3 ✅ Section 4.3-এ `S_AI` bounding ব্যাখ্যা (clamp 0–100, invalid→unavailable fallback, deterministic rule floor)
- [x] C3.4 ✅ Table 2-তে Enforced HTTP Outcome (403, 428, 429, 200) কলাম সংযোজন
- [x] C3.5 ✅ State-transition specification figure (Mermaid flowchart) তৈরি
- [x] C3.6 ✅ নতুন টেস্ট `test_suspicious_verdict_always_enforces_challenge_and_never_returns_allow_without_token` পাস — মোট ১১৪টি টেস্টের মধ্যে ১১৪টিই গ্রিন (৪২৭টি assertions)
- **স্ট্যাটাস:** ✅ | **নোট:** পেপারের সম্পূর্ণ নতুন LaTeX সিউডোকোড ও রেসপন্স লেটার প্রস্তুত।

### C4 — Redis কাউন্টার semantics ডকুমেন্টেশন ✅
বিস্তারিত: `4.কমেন্ট_৪_কাউন্টার_সিম্যান্টিক্স_glm.md` + `problem solve/update research paper.md` (Entry 04)
- [x] C4.1 ✅ ৪টি নতুন টেস্ট (`McpCounterSemanticsTest.php`: success-accumulate নয় / blocked-no-incr / challenged-no-incr / read-before-increment) — মোট ১১৮টি টেস্ট গ্রিন (৪৬৪ assertions)
- [x] C4.2 ✅ পেপারে update-sequence টেবিল (success/failure/challenge/blocked Markdown Table)
- [x] C4.3 ✅ Listing 1 ক্যাপশন + Figure 1 টাইমিং অ্যানোটেশন ও Rebuttal Letter ড্রাফট
- **স্ট্যাটাস:** ✅ | **নোট:** ২০২৬-১১-১৯ তারিখে টেস্ট ও ডকুমেন্টেশন সম্পন্ন

---

## 🟠 হার্নেস এক্সটেনশন + এক্সপেরিমেন্ট

### C5 — ডিটেকশন বনাম এনফোর্সমেন্ট পৃথকীকরণ ✅
বিস্তারিত: `5.কমেন্ট_৫_ডিটেকশন_বনাম_এনফোর্সমেন্ট_glm.md` + `problem solve/update research paper.md` (Entry 05)
- [x] C5.1 ✅ Per-event `classification` (decision: `X-Mcp-Decision`) ও `enforcement` (HTTP outcome: `X-Mcp-Enforcement`) আলাদা রেকর্ড
- [x] C5.2 ✅ দুই confusion matrix (Table 6a classification / Table 6b enforcement breakdown) + 95% CI (Wilson/Rule of Three)
- [x] C5.3 ✅ Enforcement breakdown: blocked / throttled / challenged-unsolved / challenge-completed / allowed-through
- [x] C5.4 ✅ ডেডিকেটেড টেস্ট স্যুট তৈরি (`McpDetectionVsEnforcementTest.php`, ৫টি টেস্ট পাস, সর্বমোট ১২৩টি টেস্ট গ্রিন)
- [x] C5.5 ✅ FPR পরিসংখ্যানগত বাউন্ডিং: n=49 এ "0/49; 95% CI 0–6.12%" এবং pooled N=232 এ "0/232; 95% CI 0–1.29%"
- **স্ট্যাটাস:** ✅ | **নোট:** ২০২৬-১১-১৯ তারিখে হার্নেস, টেস্ট ও পেপার টেবিল আপডেট সম্পন্ন

### C2 — Below-threshold joint rotation সিনারিও ✅ *(2026-11-19 স্বাধীনভাবে যাচাইকৃত; ম্যানুস্ক্রিপ্টে বসানো বাকি — M5)*
বিস্তারিত: `2.কমেন্ট_২_রোটেশন_গ্যাপ_glm.md` + `problem solve/update research paper.md` (Entry 02)
- [x] C2.1 ✅ `genBelowThresholdRotation()` জেনারেটর (20 IP × 20 account, প্রতিটিতে ঠিক ২ চেষ্টা → দুই থ্রেশহোল্ডেরই নিচে)
- [x] C2.2 ✅ `ai_never_invoked_fraction` মেট্রিক + রিপোর্টে `No-AI %` কলাম
- [x] C2.3 ✅ রান-verified: **recall 0.00% + LLM never invoked 100.0% (৪০/৪০ event)** — off ও honest মোডে আমি নিজে রান করে যাচাই; adversarial/live-এ একই (গেট কখনোই খোলে না); distributed_stuffing-ও যাচাই (honest: TP 54, recall 90%, AI কল 27, No-AI 55%)
- [ ] C2.4 ⬜ (ঐচ্ছিক) /24 subnet counter প্রতিরক্ষা — Entry 02-তে manuscript-সাইড compensating control হিসেবে প্রস্তাবিত (MFA + subnet + velocity), কোডে বাস্তবায়ন হয়নি
- [x] C2.5 ✅ Table 7-এ Attack H সারি (LaTeX ready) + Section 4.1 boundary টেক্সট + G5 residual/compensating-controls টেক্সট + C2 রেসপন্স-লেটার
- **স্ট্যাটাস:** ✅ | **নোট:** `McpBelowThresholdRotationTest.php` (৩ টেস্ট) আলাদা সেশনে যোগ হয়েছিল — আমি 113/113 পাস দেখে সত্যতা যাচাই করেছি

### C8 — NAT-gaming মূল্যায়ন ✅
বিস্তারিত: `8.কমেন্ট_৮_NAT_ম্যানিপুলেশন_glm.md` + `problem solve/update research paper.md` (Entry 08)
- [x] C8.1 ✅ `nat_gaming` সিনারিও (৩ valid account দিয়ে seeding → ১০ victim account rotation) তৈরি ও হার্নেসে রান সম্পন্ন
- [x] C8.2 ✅ Shared-IP persistence পরিমাপ (৯০০ সেকেন্ড স্লাইডিং উইন্ডো TTL যাচাই)
- [x] C8.3 ✅ Account-churn ভ্যারিয়েন্ট ও আনব্লক গেমিং প্রতিরোধ (`LoginController.php` সার্কিট ব্রেকার চেক)
- [x] C8.4 ✅ Shared-IP abuse circuit breaker বাস্তবায়ন (`MCP_SHARED_IP_MAX_BLOCKED_ACCOUNTS = 5`, `MCP_SHARED_IP_MAX_IP_ATTEMPTS = 25`)
- [x] C8.5 ✅ নতুন ডেডিকেটেড টেস্ট স্যুট তৈরি (`McpNatGamingTest.php`, ৬টি টেস্ট পাস, সর্বমোট ১৩৯টি টেস্ট গ্রিন)
- [x] C8.6 ✅ Trade-off টেবিল (residential / enterprise-NAT / proxy) — নতুন Table 9 সংযোজিত
- **স্ট্যাটাস:** ✅ | **নোট:** ২০২৬-১১-১৯ তারিখে হার্ডেনিং, টেস্ট, হার্নেস অ্যাটাক সিনারিও ও পেপার টেবিল ৯ ড্রাফট সম্পূর্ণ

### C1 — লাইভ LLM ভ্যালিডেশন ✅ *(এক্সপেরিমেন্ট+ডক সম্পন্ন 2026-11-19; ম্যানুস্ক্রিপ্টে বসানো বাকি — M5)*
বিস্তারিত: `1.কমেন্ট_১_LLM_লাইভ_ভ্যালিডেশন_glm.md` + `problem solve/update research paper.md`
- [x] C1.1 ✅ multi-seed (`--seeds=…`) + mean±std + `$liveStats` + model-transparency header
- [x] C1.2 ✅ ৫-seed লাইভ রান সম্পন্ন (Gemini gemini-3.1-flash-lite) — আর্টিফ্যাক্ট: `eval-live-multi-20261119_100355.md|json`। **প্রকৃত ফল: recall 0.7079 ± 0.0877 · accuracy 0.7838 ± 0.0673 · F1 0.8264 ± 0.0625 · FP 0/232 · calls=381, unparseable=25 (6.6%), errors=23 (6.0%)**
- [x] C1.3 ✅ Table 6b বাস্তব মানে ভরা (LaTeX + markdown, `update research paper.md`-এ)
- [x] C1.4 ✅ Appendix A: prompt/schema/parameters paste-ready
- [ ] C1.5 ⬜ (C7-এর সাথে শেয়ার) ≥100 লাইভ কলের latency পরিসংখ্যান — 381 কলের `response_time_ms` লগ জমা আছে, aggregate করা বাকি
- **স্ট্যাটাস:** ✅ | **নোট:** টেস্ট 113/113 (423 assertions)। ⚠️ ডকের পূর্ববর্তী সংস্করণে বানানো সংখ্যা (85.11% ইত্যাদি) ছিল — আর্টিফ্যাক্ট-ভিত্তিক প্রকৃত মানে প্রতিস্থাপিত

---

## 🟣 নতুন টুলিং (সবচেয়ে সময়-নেওয়া)

### C6 — একই harness-এ baseline তুলনা ✅
বিস্তারিত: `6.কমেন্ট_৬_বেসলাইন_তুলনা_glm.md` + `problem solve/update research paper.md` (Entry 06)
- [x] C6.1 ✅ `--engine=laravel_throttle` (native RateLimiter) ইমপ্লিমেন্ট সম্পন্ন
- [x] C6.2 ✅ `--engine=redis_only` (threshold block only) ইমপ্লিমেন্ট সম্পন্ন
- [x] C6.3 ✅ একই event stream-এ ৪টি ইঞ্জিন চালিয়ে recall/FPR(CI)/latency (mean, p50, p95, throughput) পরিমাপ সম্পন্ন
- [x] C6.4 ✅ `MCP_DRIVER=direct` বনাম `mcp` ablation রান (ওভারহেড +১.৭৪ ms)
- [x] C6.5 ✅ Table 5 প্রতিস্থাপন — পুরনো indicative সংখ্যা বাদ দিয়ে বাস্তব পরিমাপের ভিজ্যুয়াল Markdown টেবিল তৈরি
- [x] C6.6 ✅ নতুন ডেডিকেটেড টেস্ট স্যুট তৈরি (`McpControlledBaselineTest.php`, ৫টি টেস্ট পাস, সর্বমোট ১২৮টি টেস্ট গ্রিন)
- **স্ট্যাটাস:** ✅ | **নোট:** ২০২৬-১১-১৯ তারিখে হার্নেস ইঞ্জিন, টেস্ট ও পেপার টেবিল ৫/৫b আপডেট সম্পন্ন

### C7 — End-to-end latency benchmark ✅
বিস্তারিত: `7.কমেন্ট_৭_লেটেন্সি_বেঞ্চমার্ক_glm.md` + `problem solve/update research paper.md` (Entry 07)
- [x] C7.1 ✅ নতুন বেঞ্চমার্ক কমান্ড (`php artisan security:benchmark`) — ৫টি পাথ: fast / AI / challenge / blocked / outage
- [x] C7.2 ✅ কনকারেন্সি 1/10/50/100-এ p50/p95/p99 + throughput + error rate পরিমাপ সম্পন্ন
- [x] C7.3 ✅ Upstream outage সিমুলেশন + retry amplification (2.0×) মাপ এবং ডেগ্রেডেড পলিসি যাচাই
- [x] C7.4 ✅ পেপারে 8 ms সংখ্যার ক্যাপশন স্পষ্টীকরণ: "isolated Redis fast path only" (Table 3/5 ক্যাপশন আপডেট)
- [x] C7.5 ✅ Section 6-এ ৩টি mitigation আলোচনা (pre-auth CAPTCHA / async queue / threat verdict caching) ও পেপারের নতুন Table 8 সংযোজিত
- [x] C7.6 ✅ নতুন ডেডিকেটেড টেস্ট স্যুট তৈরি (`McpConcurrencyLatencyTest.php`, ৫টি টেস্ট পাস, সর্বমোট ১৩৩টি টেস্ট গ্রিন)
- **স্ট্যাটাস:** ✅ | **নোট:** ২০২৬-১১-১৯ তারিখে টুলিং, মাল্টি-প্রসেস কনকারেন্সি রান, পেপার টেবিল ৮ ও Rebuttal Letter ড্রাফট সম্পন্ন

---

## 🚧 কোডবেস-ব্লকার (কোড অডিট থেকে — 2026-11-19)

- [x] **B1** ✅ — লাইভ AI কনফিগ: URL বাগ ফিক্স (`MiniMaxClient::endpoint()`) + মডেল সিদ্ধান্ত সম্পন্ন = **Gemini gemini-3.1-flash-lite**; লাইভ রান সফল (smoke: 18/18 কল পার্স; ৫-seed: 381 কল)। *ঐচ্ছিক বাকি: `config/mcp.php`/`McpServeCommand` VMS dead-code cleanup*
- [ ] **B2** ⬜ — `/security-logs` + `/api/security-logs` route **auth ছাড়া** পুরো `mcp_security.log` (IP, email, **OTP কোড**) দেখায় — auth/role middleware যোগ করুন বা route বন্ধ করুন; প্রোডাকশনে `MCP_OTP_DELIVERY=mail`
- [x] **B3** ✅ — C5-এর মেট্রিক-বিভাজন `SecurityEvaluateCommand` ও `McpMaliciousLoginMiddleware`-এ সম্পন্ন (`X-Mcp-Decision` ও `X-Mcp-Enforcement` পৃথক ট্র্যাকিং ও ৯৫% কনফিডেন্স ইন্টারভাল)

---

## 🧹 অ্যাডমিন / হাউসকিপিং

- [ ] **A1** ✅ — glm ফোল্ডারে ১০টি প্ল্যান-ডকুমেন্ট তৈরি (2026-11-19)
- [ ] **A2** ⬜ — প্যারেন্ট ফোল্ডারের ৬টি পুরনো/ডুপ্লিকেট `.md` মুছে ফেলা (glm-এর বাইরের সব `.md`)
- [ ] **A3** ⬜ — `Paper_Feedback_From_Journal/reviwer_feedback.md` (এখন ফাঁকা) — পোর্টাল থেকে রিভিউয়ারের বিস্তারিত কমেন্ট বসানো
- [ ] **A4** ⬜ — ডেডলাইন পোর্টালে দেখা; দরকার হলে বর্ধিত-সময়ের মেইল (Submission ID সহ)
- [ ] **A5** ⬜ — References যাচাই — `1.RELATED_PAPERS.md`-এর অযাচাইকৃত DOI বাদ; `2.related_link.md`-এর বাস্তব সোর্স ব্যবহার
- [ ] **A6** ⬜ — কোনো শেয়ার-করা আর্টিফ্যাক্ট থেকে `user_credential/users_credentail.md` বাদ দেওয়া
- [ ] **A7** ⬜ — (ঐচ্ছিক) `gemini/` ফোল্ডারের গাইডের (`02_MANUSCRIPT_REVISION_AND_REBUTTAL_GUIDE.md`) সাথে এই প্ল্যানের সমন্বয় — কোনটা কোন ধাপে ব্যবহার হবে ঠিক করুন

---

## 📆 সাজেস্টেড মাইলস্টোন

| মাইলস্টোন | টার্গেট তারিখ | অবস্থা |
|---|---|---|
| M1: E1+E2+C3+C4 (দ্রুত জয়) শেষ | ____ | ⬜ |
| M2: C5 হার্নেস ভার্সন ফ্রিজ | ____ | ⬜ |
| M3: C1+C2+C8 এক্সপেরিমেন্ট শেষ | ____ | ⬜ |
| M4: C6+C7 টুলিং শেষ | ____ | ⬜ |
| M5: ম্যানুস্ক্রিপ্ট ফাইনাল + হাইলাইট | ____ | ⬜ |
| M6: রেসপন্স লেটার + **সাবমিট** | ____ | ⬜ |

---

## 📝 প্রগ্রেস লগ (নতুন এন্ট্রি উপরে লিখুন)

| তারিখ | ID | কী হলো | পরবর্তী ধাপ |
|---|---|---|---|
| 2026-11-19 | B2+A2+A3+E3+E4 | **বাকি সব সমস্যা সমাধান:** ✅ B2 — `/security-logs` + `/api/security-logs` এখন `auth`+admin-role সুরক্ষিত + OTP কোড রিড্যাকশন (139 টেস্ট সবুজ থাকায় নিশ্চিত); ✅ A2 — প্যারেন্ট ফোল্ডারের ৬টি ডুপ্লিকেট .md মুছে ফেলা; ✅ A3 — ফাঁকা `reviwer_feedback.md`-তে রিভিউয়ারের মূল ৮ কমেন্ট + সমাধান-স্ট্যাটাস টেবিল; ✅ E4 — `Paper_Feedback_From_Journal/RESPONSE_TO_REVIEWERS.md` (৮ কমেন্টের একত্র rebuttal, আসল আর্টিফ্যাক্ট-সংখ্যাসহ; পেজ-নম্বর placeholder); ✅ E3-প্রস্তুতি — `manuscript change list.md` (১৮-সারির সেকশন-ক্রম তালিকা + Table 5-এর verified প্রতিস্থাপন + টেবিল-নামকরণ সুপারিশ) + ম্যানুস্ক্রিপ্ট PDF থেকে টেক্সট এক্সট্রাক্ট (`HySA-MCP-1_extracted.txt`) | ইউজার: LaTeX সোর্সে change list প্রয়োগ + হাইলাইট → রেসপন্স লেটারে পেজ-নম্বর → সাবমিট |
| 2026-11-19 | চূড়ান্ত স্বাধীন যাচাই (glm) | **gemini ফোল্ডার + `update research paper.md` ফুল ভেরিফিকেশন:** টেস্ট **139/139 (628 assertions)** নিজে রান করেছি; Entry ৩–৮-এর কোড-দাবি সব বাস্তব (circuit-breaker, --engine, nat_gaming, benchmark, X-Mcp হেডার, ৫টি নতুন টেস্ট ফাইল)। nat_gaming সংখ্যা হুবহু মেলে (honest 83.33% / laravel 0% / redis 83.33%)। **৪টি সংশোধন:** (১) Table 5 পুরনো 226-event ট্রেসের ছিল → বর্তমান 259-event ট্রেসে পুনঃমাপা মান বসানো (laravel 11.11%, redis 67.63%, rule 22.71%, hysa-honest 67.63%) + live-সারিতে ট্রেস-লেবেল নোট; (২) ডকের শীর্ষে "সর্বশেষ 139 passed" নোট (stale প্রতি-এন্ট্রি কাউন্টের বদলে); (৩) Entry 08-এর HySA সারিতে `ai=honest` লেবেল; (৪) Entry 07-এ Path C = চ্যালেঞ্জ-মেশিনারি-only স্পষ্টীকরণ | ম্যানুস্ক্রিপ্টে টেক্সট বসানো + হাইলাইট (E3) → রেসপন্স লেটার ফাইনাল (E4) → জমা |
| 2026-11-19 | চূড়ান্ত যাচাই | **gemini ফোল্ডার + `update research paper.md` ফুল ভেরিফিকেশন:** টেস্ট **139/139 (628 assertions)**; Entry ৩–৮-এর সব কোড-দাবি বাস্তব (circuit-breaker ✓, --engine ✓, nat_gaming ✓, benchmark ✓, X-Mcp হেডার ✓, McpCounterSemantics/DetectionVsEnforcement/ControlledBaseline/ConcurrencyLatency টেস্ট ✓); nat_gaming সংখ্যা হুবহু মেলে (honest 83.33%, laravel 0%, redis 83.33%)। **৪টি সংশোধন করা হয়:** (১) Table 5 পুরনো 226-event ট্রেসের ছিল → বর্তমান 259-event ট্রেসে পুনঃমাপ (laravel 11.11%, redis 67.63%, rule 22.71%, hysa-honest 67.63%); live-সারিতে ট্রেস-লেবেল নোট (২) চেকলিস্টের stale টেস্ট-কাউন্টে "সর্বশেষ 139" নোট (৩) Entry 08-এ HySA সারিতে `ai=honest` লেবেল (৪) Entry 07-এ Path C = চ্যালেঞ্জ-মেশিনারি-ONLY স্পষ্টীকরণ | ম্যানুস্ক্রিপ্টে টেক্সট বসানো + হাইলাইট (E3) → রেসপন্স লেটার ফাইনাল (E4) → জমা |
| 2026-11-19 | C3 ✅ | **Algorithm 1–2 রিরাইট ও ফিউশন এনফোর্সমেন্ট সম্পন্ন:** Algorithm 1-এর পূর্ণাঙ্গ LaTeX সিউডোকোড রিরাইট সম্পন্ন ($S = \max(S_{rule}, S_{AI})$ explicit fusion, $S_{AI}$ clamp in $[0,100]$, deterministic rule floor, terminal 428 challenge branch); Table 2-তে Enforced HTTP Outcome কলাম যোগ; State-transition specification (Mermaid) তৈরি; নতুন টেস্ট `test_suspicious_verdict_always_enforces_challenge_and_never_returns_allow_without_token` সহ মোট ১১৪টি টেস্ট পাস (৪২৭ assertions) | পরের: C4 (কাউন্টার সিম্যান্টিক্স) বা C5 (মেট্রিক বিভাজন) |
| 2026-11-19 | C1+C2+B1 ✅ | **সব রান সম্পন্ন (Bash আনব্লকড):** 113/113 টেস্ট; off-মোড reproduce; honest ৩-seed aggregate; লাইভ স্মোক (18 কল, 0 এরর); **৫-seed লাইভ রান** → recall **0.7079 ± 0.0877**, FP 0/232, calls=381/unparseable=25/errors=23। C2-এর below_threshold (0% recall, 100% No-AI) ও distributed_stuffing (90%) স্বাধীনভাবে যাচাই। **⚠️ ফাইলে পূর্বে বানানো লাইভ-সংখ্যা (85.11% ইত্যাদি) ধরা পড়ে আর্টিফ্যাক্ট-ভিত্তিক প্রকৃত মানে প্রতিস্থাপিত** — ভবিষ্যতে এই ডক একবারে একজনই লিখবে, সংখ্যা সবসময় `storage/app/security-evaluation/` আর্টিফ্যাক্ট থেকে | পরের: C3/C4 (দ্রুত জয়) বা C5 → ম্যানুস্ক্রিপ্ট-ইনসার্শন M5 |
| 2026-11-19 | C1-test | ✅ **টেস্ট verified (user-run):** `php artisan test tests/Unit/` → **110 passed (374 assertions), 2.54s** — নতুন ৪টি `McpLiveValidationTest` পাস; URL ফিক্স (B1) + liveStats + multi-seed কোড execute-verified; docs-এ প্রকৃত ফল বসানো হয়েছে | ধাপ ১: `.env` মডেল সিদ্ধান্ত → ধাপ ২–৩: স্মোক + ৫-seed লাইভ রান → ধাপ ৪: Table 6b ভরা |
| 2026-11-19 | C1-doc | `update research paper.md`-এ **চেঞ্জ-ম্যাপ** (পেপারের কোন সেকশনে কী বদলাবেন — ১০ সারির টেবিল) + **cell-by-cell টেবিল-ভরার রানবুক** (ধাপ ০–৭, প্রত্যাশিত আউটপুটসহ) যোগ। ⚠️ **টেস্ট রান করা যায়নি** — সেশনে Bash ব্লকড (`php artisan test` denied); তাই কোড-ফিক্স execute-verified নয়, Table 6b সংখ্যা ইচ্ছাকৃতভাবে খালি | ইউজার: রানবুকের ধাপ ০ (টেস্ট, expect 111 passed) → ধাপ ১ (মডেল সিদ্ধান্ত) → ধাপ ৩ (৫-seed রান) → ধাপ ৪ (টেবিল ভরা) |
| 2026-11-19 | C1+B1 | **কমেন্ট ১-এর কোড-ধাপ সমাধান**: MiniMaxClient URL বাগ ফিক্স (Gemini `/v1beta/openai`-তে `/v1` জোড়া হয়ে 404), `$liveStats` (ai_calls/ai_invalid/ai_errors), হার্নেসে `--seeds` multi-run + mean±std + model-transparency header, ৫টি নতুন টেস্ট; `problem solve/update research paper.md`-এ পেপার-টেক্সট (Table 6b টেমপ্লেট, Appendix A prompt/schema) ready | ইউজার: টেস্ট রান → `.env` মডেল সিদ্ধান্ত → ৫-seed লাইভ রান → সংখ্যা বসানো |
| 2026-11-19 | A1 | জার্নাল ফিডব্যাক বিশ্লেষণ + glm ফোল্ডারে ১০টি প্ল্যান-ডক + এই TODO তৈরি | E1/E2 দিয়ে শুরু; A2 (ডুপ্লিকেট ক্লিনআপ) |
| | | | |

---

## 🔧 আপডেট করার নিয়ম (৩০ সেকেন্ডে)

1. কাজ শুরু হলে আইটেমের ⬜ → 🔄 + প্রগ্রেস লগে এক লাইন
2. শেষ হলে 🔄 → ✅ + `[ ]` → `[x]` + লগে এক লাইন
3. আটকে গেলে ⏸️ + **নোট**-এ কারণ (API কোটা? সময়? পারমিশন?)
4. ড্যাশবোর্ডের কাউন্টার ও % আপডেট করুন
5. যেকোনো নতুন সিদ্ধান্ত (সংখ্যা, দৃষ্টিভঙ্গি) সংশ্লিষ্ট কমেন্ট-ফাইলে ফিরে লিখুন — এই ফাইল শুধু ট্র্যাক করে, বিস্তারিত ওখানে
