# 📄 Manuscript Change List — এক জায়গায় সব পরিবর্তন (LaTeX সোর্সে প্রয়োগের চূড়ান্ত তালিকা)

**ম্যানুস্ক্রিপ্ট:** HySA-MCP-1 (IJIS / Springer) · **সাবমিশন ID:** `c372b849-6b41-418e-ad06-5c08e86be13a`
**তারিখ:** 2026-11-19 · **সোর্স-টেক্সট (রেফারেন্স):** `Research/Paper_Feedback_From_Journal/HySA-MCP-1_extracted.txt`
**বিস্তারিত paste-ready টেক্সট:** `update research paper.md`-এর Entry 01–08 (প্রতিটি সারিতে এন্ট্রি-নম্বর দেওয়া)

> ⚠️ **নিয়ম:** নিচের প্রতিটি পরিবর্তন LaTeX সোর্সে বসিয়ে **হাইলাইট/রেড করুন** (জার্নালের নিয়ম)। সংখ্যার কোনোটাই পরিবর্তন করবেন না — সবগুলো যাচাইকৃত আর্টিফ্যাক্ট থেকে।

---

## ক) মডেলের নাম — সর্বত্র (E1 + B1)

| খুঁজুন | বদলান | কোথায় |
|---|---|---|
| `MiniMax-M2.1` / "MiniMax Large Language Model" | **Google Gemini (`gemini-3.1-flash-lite`)** — প্রথম উল্লেখে বিস্তারিত, পরে সংক্ষেপ | Abstract, §1.1, §4.1, §4.2, Algorithm 1 (line ~576), §5.1, §6 — ~১০+ জায়গা |
| "If the Minimax API goes down…" | "If the **upstream LLM API** experiences downtime… tiered fail-closed and degraded-security posture" | Abstract (lines 29–32), §1.1 (line 155) |

**যুক্তি (§4.1-এ বসান):** মডেল-সাবস্টিটিউশন দাবিটি এখন এমপিরিক্যালি প্রমাণিত — কোর লজিক অপরিবর্তিত রেখে MiniMax → Gemini লাইভ স্যুইচ হয়েছে (Entry 01)।

---

## খ) সেকশন-ক্রমানুসারে পরিবর্তন

| # | সেকশন | পরিবর্তন | এন্ট্রি |
|---|---|---|---|
| ১ | **Abstract** | মডেল-নাম (ক-দেখুন) + "40 of 186 events (≈21.5%)" invocation সংখ্যাটি আপডেট: **~76/186 (41%) live** বা রাখলে স্পষ্ট করে simulated-config বলুন | 01 |
| ২ | **§1 Introduction** | কন্ট্রিবিউশন তালিকায় "live validation across 5 seeds" + "controlled baseline comparison" যোগ | 01, 06 |
| ৩ | **§4.1** | (ক) মডেল-নাম; (খ) escalation-gate boundary প্যারা: *"AI analysis is invoked strictly when either counter crosses θ_ip=5 or θ_email=3; a joint-rotation adversary staying below both is not escalated — quantified in §5.4 (Attack H)"*; (গ) NAT hardening (দেখুন সারি ১১) | 02 |
| ৪ | **§4.2** | মডেল-নাম + prompt/schema-এর পয়েন্টার Appendix A-তে | 01 |
| ৪.৫ | **Fig. 1 (একমাত্র ফিগার)** | পুরনো আর্কিটেকচার-চিত্র প্রতিস্থাপন → নতুন PNG (`Research/System Architecture Algorithm Flow/Fig:1-...png`, 2026-11-19 রেন্ডার) + নতুন ক্যাপশন; **নতুন Figure 2** = Entry 03-এর state-transition diagram (§4.4-এ); ঐচ্ছিক: ফ্লোচার্ট+সিকোয়েন্স supplementary-তে — বিস্তারিত Entry 09 | 09 |
| ৫ | **Algorithm 1** | পুরো রিরাইট (Entry 03-এর LaTeX): `S ← max(S_rule, S_AI)` স্পষ্ট; terminal non-MALICIOUS ব্রাঞ্চে `issue token; return 428` (Allow নয়); early block-exit; NAT-scoped block; S_AI-র bounding (clamp [0,100], invalid→unavailable, floor) | 03 |
| ৬ | **Algorithm 2 / Table 2** | Table 2-তে **Enforced HTTP Outcome** কলাম: 0–29→200, 30–49→428(captcha), 50–79→428(otp), 80–100→403; degraded মোডে 50–79→N-strike 429 নোট | 03 |
| ৭ | **§4.4** | NAT hardening প্যারা: **Shared-IP Abuse Circuit Breaker** (≥5 blocked accounts বা >25 aggregate failure → IP quarantine 900s), **Unblock-Gaming Resistance** (trip হলে success-এও blocked:ip মুছে না), **Time-Bounded Persistence** (900s TTL) | 08 |
| ৮ | **§5.1 (Experimental setup)** | মডেল-নাম + parameters: temperature=0, max_tokens=200, timeout 10s, 1 retry; harness seed-ভিত্তিক reproducibility বাক্য | 01 |
| ৯ | **§5.3** | পুরনো Table 5-এর **indicative সংখ্যা পুরো বাদ** → নতুন মাপা টেবিল (নিচের ৯-ক দেখুন) + §5.3.1 MCP ablation (নিচের ৯-খ) | 06 |
| ১০ | **§5.4** | (ক) নতুন প্যারা "Live Model Validation" (Entry 01-খ); (খ) Table 6 → **6a (simulated, ক্যাপশনে "Simulated-analyst ablation")** + **6b (live, LaTeX Entry 01-এ)**; (গ) Table 6a/6b-র পুনঃব্যাখ্যা: Table 6a=classification, 6b=enforcement breakdown (Entry 05-এর ৬a/৬b — নামকরণ সংঘর্ষ লক্ষ করুন: Entry 01-এর 6b=live, Entry 05-এর 6b=enforcement → **চূড়ান্ত নামকরণ ঠিক করুন: Table 6=simulated ablation, Table 7=live 5-seed, Table 8=classification-vs-enforcement** অনুমোদিত); (ঘ) distributed_stuffing 90%-র ব্যাখ্যা + Attack H (Entry 02) | 01, 02, 05 |
| ১১ | **§5.6 / §6 Discussion** | (ক) unparseable 6.6% + transport-error 6.0% → rule-floor fallback আলোচনা; (খ) latency mitigations (pre-analysis CAPTCHA gating, async queued assessment, verdict caching 300s); (গ) থ্রেট মডেল G5 আপডেট: joint-rotation residual + compensating controls (MFA, /24 subnet, velocity) | 01, 02, 07 |
| ১২ | **Table 7 (attack catalogue)** | নতুন **Attack H** সারি: `below_threshold_rotation` — recall 0.00% সব মোডে, LLM never invoked 100% (LaTeX Entry 02-তে); G-সারির সংখ্যা যাচাই করুন (adversarial recall বর্তমান ডকে 60.2% লেখা — পুরনো 79.6%-র সাথে অমিল, **আর্টিফ্যাক্ট দেখে চূড়ান্ত করুন**) | 02 |
| ১৩ | **নতুন Table 8 (latency)** | ৫-পাথ benchmark (Entry 07): Path A p50 1.38ms…, Path B live p50 ~1.9s, Path C challenge-machinery only (স্পষ্ট ক্যাপশন), Path D ≤2ms post-block drop, Path E bounded 2.0× retry | 07 |
| ১৪ | **নতুন Table 9 (NAT trade-off)** | Residential / Enterprise-NAT / Proxy তুলনা + nat_gaming মাপ (honest 83.33%, laravel 0%, redis 83.33% + collateral 0%) | 08 |
| ১৫ | **§6 Threat model** | মডেল-নাম (semi-trusted LLM) + G5 (সারি ১১-গ) | 08, 02 |
| ১৬ | **§7 Limitations — নতুন সেকশন (E2)** | বুলেট: (১) below-threshold joint rotation undetectable (Attack H); (২) low-and-slow recall 0; (৩) FPR 0/232 → 95% CI 0–1.63% (Wilson), n ছোট; (৪) live-metric 12.5% fallback (6.6% unparseable + 6.0% transport); (৫) simulated trace (9 সিনারিও, seeded) বনাম live trace (৭ সিনারিও) — ট্রেস-কম্পোজিশন স্পষ্ট; (৬) একক-ডোমেইন (insurance) মূল্যায়ন | 02, 05, 01 |
| ১৭ | **Appendix A** | Verbatim system+user prompt (pseudonymized placeholders), JSON schema, response-validation নিয়ম, sampling/transport params (Entry 01-খ) | 01 |
| ১৮ | **Appendix (Reproduction)** | কমান্ড: `security:evaluate --ai=… --seeds=… --engine=…`, `security:benchmark --path=all`, আর্টিফ্যাক্ট-ফাইল প্যাটার্ন, seed তালিকা | 01, 06, 07 |

### ৯-খ. Table 5-এর প্রতিস্থাপন (verified, মূল পেপার-ট্রেস: ১৮৬ event / 137 attack / 49 legit, seed 42 — honest ও off পেপারের সংখ্যার সাথে হুবহু reproduce)
| Engine | Recall | Precision | FPR | AI calls |
|---|---|---|---|---|
| laravel_throttle | 16.79% (23/137) | 1.0 | 0% | 0 |
| redis_only | 83.94% (115/137) | 1.0 | 0% | 0 |
| rule_based | 21.17% (29/137) | 1.0 | 0% | 89 |
| HySA (honest, direct) | 83.94% (115/137) | 1.0 | 0% | 40 |
| (রেফারেন্স) HySA adversarial | 79.56% (109/137) | 1.0 | 0% | 115 |
| HySA (live, ৫-seed) | **70.79% ± 8.77%** | 1.0 | 0% (CI 0–1.63%) | 381 |

*(Latency/throughput কলাম Entry 06-এর benchmark-মান থেকে; live-সারির ট্রেস-নোট ক্যাপশনে রাখুন।)*

### ৯-খ-২. MCP ablation (Table 5b): direct 1.56ms বনাম MCP 3.30ms (+1.74ms), recall অপরিবর্তিত — Entry 06।

---

## গ) চূড়ান্ত প্রেরণের চেকলিস্ট (E3/E4)

- [ ] LaTeX সোর্সে উপরের ১–১৮ প্রয়োগ, প্রতিটি পরিবর্তন **লাল/হাইলাইট**
- [ ] টেবিল-নামকরণ এককরণ (সারি ১০-গ-এর সুপারিশ অনুযায়ী)
- [ ] `RESPONSE_TO_REVIEWERS.md`-এর পেজ-নম্বরগুলো ফাইনাল PDF-এর সাথে মিলিয়ে বসান (এখন "Pages 10–13" ইত্যাদি placeholder)
- [ ] Cover letter আপডেট + পোর্টালে আপলোড: (১) revised manuscript (২) response letter
- [ ] ডেডলাইন পোর্টালে যাচাই
