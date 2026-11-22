# 01. Reviewer Comments: Point-by-Point Action Plan

**Journal:** *International Journal of Information Security (IJIS)* — Springer Nature  
**Manuscript:** *HySA-MCP: A Hybrid AI-Driven Secure Authentication Framework with Model Context Protocol (MCP) Integration*  
**Scope:** রিভিউর ৮টি প্রধান টেকনিক্যাল মন্তব্যের পয়েন্ট-বাই-পয়েন্ট পুঙ্খানুপুঙ্খ সমাধান ও করণীয়।

---

## Comment 1: Validation of LLM-Driven Detection Gains

### রিভিউয়ারের মূল মন্তব্য:
> *"The increase in recall from 21.2% (rules only) to 83.9% (rules + AI) is derived from a seeded evaluation whose 'honest' AI mode is described as a competent simulated AI. Consequently, the reported gain does not yet establish detection effectiveness of the deployed MiniMax-M2.1 integration. The authors should repeat the evaluation using live model responses, disclose the prompt and response schema, and report variation across repeated runs and representative attack distributions. Confusion matrices for live-model outcomes should be distinguished from simulation results."*

### সমস্যার মূল কারণ:
পেপারে যে রিকল বৃদ্ধির টেবিল (Table 6) দেওয়া হয়েছিল, সেখানে এআই-এর ফলাফল নেওয়া হয়েছিল একটি সিমুলেটেড ফাংশন (`honest` mode) থেকে, সত্যিকারের লাইভ এআই এপিআই কল করে নয়। একজন জার্নাল রিভিউয়ার এটি সহজেই ধরে ফেলেছেন।

### কোডের বর্তমান অবস্থা:
- আমাদের কমান্ড `app/Console/Commands/SecurityEvaluateCommand.php` এ ইতিমধ্যে `--ai=live` অপশন যুক্ত করা আছে।
- সিস্টেমে বর্তমানে Google Gemini লাইভ কানেকশন সক্রিয় আছে।

### যা যা করতে হবে (Step-by-Step Action):
1. **লাইভ এআই ইভালুয়েশন রান করা:**
   টার্মিনালে কমান্ডটি এক্সিকিউট করতে হবে:
   ```bash
   php artisan security:evaluate --ai=live --seed=42
   php artisan security:evaluate --ai=live --seed=100
   php artisan security:evaluate --ai=live --seed=2026
   ```
2. **লাইভ কনফিউশন ম্যাট্রিক্স সংরক্ষণ:**
   ৩টি ভিন্ন সীডের লাইভ রানের ফলাফল (True Positives, False Positives, True Negatives, False Negatives, Precision, Recall, F1) সংগ্রহ করে গড় (Mean) ও স্ট্যান্ডার্ড ডেভিয়েশন (Standard Deviation) বের করতে হবে।
3. **প্রম্পট ও রেসপন্স স্কিমা প্রকাশ করা:**
   `app/Mcp/SecurityPrompt.php` এবং `app/Mcp/Tools/AnalyzeLoginRiskTool.php` তে ব্যবহৃত সিস্টেম প্রম্পট এবং ইনপুট/আউটপুট JSON স্কিমা পেপারের সেকশন বা অ্যাপেন্ডিক্সে হুবহু যুক্ত করতে হবে।
4. **পেপারের টেবিলে পরিবর্তন:**
   সিমুলেটেড টেবিলের পাশাপাশি "Live Model Evaluation" নামে একটি ডেডিকেটেড টেবিল বসাতে হবে যাতে লাইভ ফলাফলের সত্যতা প্রমাণিত হয়।

---

## Comment 2: Detection Gap Under Joint IP and Account Rotation

### রিভিউয়ারের মূল মন্তব্য:
> *"Algorithm 1 bypasses AI analysis when both the IP and email attempt counters remain below their invocation thresholds. An adversary can exploit this gate by rotating source addresses and targeted accounts jointly, which is especially relevant to distributed credential stuffing and password spraying. The reported 90% recall for distributed stuffing therefore requires a clearer description of how the simulated events cross the escalation threshold. The authors should evaluate joint IP/account rotation, including a deliberately below-threshold adversary, and report the fraction of attacks for which the LLM is never invoked."*

### সমস্যার মূল কারণ:
আমাদের সিস্টেম এআই-কে তখনই ডাকে যখন আইপি প্রচেষ্টা $\ge 5$ অথবা ইমেইল প্রচেষ্টা $\ge 3$ হয়। আক্রমণকারী যদি বটনেট বা প্রক্সি ব্যবহার করে প্রতিটি আইপি ও ইমেইলকে এই সংখ্যার নিচে রাখে (Low-and-Slow Attack), তবে এআই কখনোই সক্রিয় হয় না। পেপারে ৯০০% আটকানোর দাবিটি তাত্ত্বিকভাবে অবাস্তব মনে হয়েছে।

### কোডের বর্তমান অবস্থা:
- `THREAT_MODEL.md` এর Section 7.2 এ এই বিষয়টি ইতিমধ্যে চিহ্নিত এবং তাত্ত্বিক সীমাবদ্ধতা হিসেবে ব্যাখ্যা করা আছে।

### যা যা করতে হবে (Step-by-Step Action):
1. **সীমাবদ্ধতা সততার সাথে স্বীকার করা (Honest Academic Framing):**
   পেপারের সেকশন ৪ এবং সেকশন ৬ এ স্পষ্টভাবে লিখতে হবে: *"Pure counter-based invocation cannot escalate below-threshold distributed attacks where each tuple $(IP_i, Email_j)$ remains strictly $< \text{threshold}$."*
2. **পরিসংখ্যান রিপোর্ট করা:**
   যেসব অ্যাটাক ইচ্ছাকৃতভাবে থ্রেশহোল্ডের নিচে থাকে (যেমন: প্রতিটি আইপি থেকে ১টি করে চেষ্টা), সে ক্ষেত্রে এআই-এর ইনভোকেশন রেট যে ০% এবং এটি রুলস ও এআই উভয়ের জন্যই একটি আর্কিটেকচারাল বাউন্ডারি, তা স্পষ্ট করা।
3. **মিটিগেশন স্ট্র্যাটেজি উল্লেখ করা:**
   ভবিষ্যতের সমাধান হিসেবে গ্লোবাল ফেইলর রেট (Global Failure Rate Spike) বা সাবনেট-লেভেল ক্লাস্টারিংয়ের মাধ্যমে কীভাবে এই ধরনের লো-এন্ড-স্লো অ্যাটাক শনাক্ত করা সম্ভব, তা "Limitations & Future Work" সেকশনে লিখে দেওয়া।

---

## Comment 3: Risk-Score Fusion and Challenge-Enforcement Consistency

### রিভিউয়ারের মূল মন্তব্য:
> *"Section 4.3 states that the effective score is $S = \max(S_{\text{rule}}, S_{\text{AI}})$, yet Algorithm 1 does not explicitly compute or use this fused score. In addition, the algorithm assigns token_428 to SUSPICIOUS requests but subsequently returns Allow in its terminal non-MALICIOUS branch. This pseudocode is difficult to reconcile with the claimed enforced HTTP 428 step-up mechanism. Please provide an unambiguous executable decision sequence (or equivalent state-transition specification), explain how $S_{\text{AI}}$ is obtained and bounded, and verify that every SUSPICIOUS outcome consistently triggers the intended challenge."*

### সমস্যার মূল কারণ:
পেপারের Algorithm 1 এর সিউডোকোডটিতে গুরুতর ভুল ছিল:
- $S = \max(S_{\text{rule}}, S_{\text{AI}})$ টেক্সটে লিখলেও অ্যালগরিদমে তা ক্যালকুলেট করা হয়নি।
- SUSPICIOUS হলে `token_428` দেওয়া হলেও অ্যালগরিদমের শেষে ভুলবশত `Allow` রিটার্ন করা হয়েছিল (যা বোঝায় রিকোয়েস্টটি পাস হয়ে গেছে, ব্লক বা চ্যালেঞ্জ হয়নি)।

### কোডের বর্তমান অবস্থা:
- কোডে `app/Services/McpSecurityService.php` এ `fuseDecision()` মেথড তৈরি আছে যা ফিউশন রুলস F1–F5 দিয়ে নিখুঁতভাবে $\max(\text{ruleScore}, \text{aiScore})$ নিশ্চিত করে।
- `app/Http/Middleware/McpMaliciousLoginMiddleware.php` এ SUSPICIOUS হলে সরাসরি **HTTP 428 Precondition Required** রেসপন্স রিটার্ন করে রিকোয়েস্ট সাথে সাথে আটকে দেওয়া হয় (Allow হয় না)।

### যা যা করতে হবে (Step-by-Step Action):
1. **Algorithm 1 ও Algorithm 2 এর সিউডোকোড সম্পূর্ণ পুনর্লিখন:**
   কোডের বাস্তব লজিক অনুযায়ী Algorithm 1 আপডেট করতে হবে।
   - ধাপ ১: $S_{\text{rule}}$ গণনা।
   - ধাপ ২: থ্রেশহোল্ড অতিক্রম করলে MCP এআই কল।
   - ধাপ ৩: $S = \max(S_{\text{rule}}, S_{\text{AI}})$ এবং F1–F5 ফিউশন রুলস প্রয়োগ।
   - ধাপ ৪: $S \ge 80 \implies \text{Block (HTTP 429)}$; $50 \le S < 80 \implies \text{Halt \& Challenge (HTTP 428)}$; $S < 50 \implies \text{Allow}$।
2. **স্টেট ট্রানজিশন ডায়াগ্রাম প্রদান:**
   একটি পরিষ্কার স্টেট ডায়াগ্রাম পেপারে যুক্ত করতে হবে যা দেখাবে কীভাবে একজন ইউজার `UNVERIFIED` $\to$ `CHALLENGED (428)` $\to$ `VERIFIED (Allow)` অথবা `BLOCKED (429)` এ স্থানান্তরিত হয়।

---

## Comment 4: Semantics and Timing of Redis Failure Counters

### রিভিউয়ারের মূল মন্তব্য:
> *"The risk score is defined using failed login attempts, but Listing 1 increments both Redis counters unconditionally and the interception middleware is positioned ahead of Laravel credential verification. It is therefore unclear whether the counters represent all login requests or confirmed failures, and when successful logins affect their values. This distinction materially changes risk scores and false-positive behavior. The authors should document the exact update sequence for success, failure, challenge, and blocked requests and add tests for legitimate users who repeatedly authenticate successfully."*

### সমস্যার মূল কারণ:
যেহেতু মিডলওয়্যারটি কন্ট্রোলারের আগে চলে, তাই ইউজার সঠিক পাসওয়ার্ড দিয়েছে নাকি ভুল দিয়েছে তা জানার আগেই রেডিস কাউন্টার বেড়ে যায়। রিভিউয়ার জানতে চেয়েছেন— এটি কি "লগইন চেষ্টার সংখ্যা" নাকি "লগইন ব্যর্থতার সংখ্যা"? এবং কোনো ইউজার সফল লগইন করলে কাউন্টার রিসেট হয় কি না?

### কোডের বর্তমান অবস্থা:
- `McpMaliciousLoginMiddleware` প্রি-অথেন্টিকেশন অ্যাটেম্পট কাউন্ট করে।
- `LoginController.php` এ ইউজার সফলভাবে লগইন করলে (`authenticated()` মেথডে) সংশ্লিষ্ট ইমেইলের ফেইলর কাউন্টার রিসেট করা হয়।

### যা যা করতে হবে (Step-by-Step Action):
1. **কাউন্টার সেমান্টিকস স্পষ্ট করা:**
   পেপারে স্পষ্টভাবে লিখতে হবে: *"Counters represent pre-authentication incoming attempt frequency over a fixed time window $\Delta t$, which is subsequently cleared for the account upon verified credential success."*
2. **কাউন্টার লাইফসাইকেল ডায়াগ্রাম বা টেবিল তৈরি করা:**
   | ইভেন্ট | আইপি কাউন্টার | ইমেইল কাউন্টার | অ্যাকশন |
   |---|---|---|---|
   | নতুন রিকোয়েস্ট | +১ বৃদ্ধি | +১ বৃদ্ধি | থ্রেশহোল্ড চেক |
   | চ্যালেঞ্জ জারি (428) | অপরিবর্তিত | অপরিবর্তিত | OTP/Captcha পাঠানো |
   | সঠিক ক্রেডেনশিয়াল | অপরিবর্তিত | **রিসেট (০)** | লগইন সফল |
   | ভুল ক্রেডেনশিয়াল | বজায় থাকে | বজায় থাকে | ব্যর্থতা লগ |
   | ব্লক (429) | লকড | লকড | এক্সেস প্রত্যাখ্যাত |
3. **টেস্ট কেস রেফারেন্স:**
   বৈধ ইউজার বারবার সফল লগইন করলে কাউন্টার না বেড়ে রিসেট হওয়ার টেস্ট পেপারের সেকশন ৪.২ এ উদ্ধৃত করতে হবে।

---

## Comment 5: Separating Threat Detection from Challenge-Based Prevention

### রিভিউয়ারের মূল মন্তব্য:
> *"The evaluation counts an unsolved HTTP 428 challenge as a 'denied' malicious event and includes it in the true-positive totals. Blocking an attack through a challenge is operationally useful, but it does not necessarily indicate that the classifier correctly identified malicious behavior. Please report detection/classification performance separately from enforcement outcomes, including explicit blocks, throttles, challenges, challenge completions, and successful unauthorized attempts. The zero observed false-positive rate is based on only 49 legitimate negatives; its uncertainty and sensitivity to legitimate users encountering challenges should be emphasized."*

### সমস্যার মূল কারণ:
অ্যাটাকার স্ক্রিপ্ট যখন HTTP 428 চ্যালেঞ্জের মুখে পড়ে থেমে গেছে, পেপারে তাকে এআই-এর শতভাগ নিখুঁত ডিটেকশন (True Positive) হিসেবে ধরে নেওয়া হয়েছিল। রিভিউয়ার যুক্তি দিয়েছেন যে, চ্যালেঞ্জ দিয়ে আটকে দেওয়া আর এআই দিয়ে সঠিকভাবে চিহ্নিত করা এক জিনিস নয়। এছাড়া মাত্র ৪৯ জন সাধারণ ইউজারের ডেটা দিয়ে "০% ফলস পজিটিভ" দাবি করা পরিসংখ্যানগতভাবে দুর্বল।

### কোডের বর্তমান অবস্থা:
- `COMPLETE_REPORT.md` তে এনফোর্সমেন্ট একশন ও ক্লাসিফিকেশন আলাদা করা শুরু হয়েছিল।

### যা যা করতে হবে (Step-by-Step Action):
1. **ডিটেকশন বনাম এনফোর্সমেন্ট আলাদা টেবিল:**
   পেপারের ফলাফল সেকশনে দুটি পৃথক টেবিল তৈরি করতে হবে:
   - **Table A (Pure Classification):** ক্লাসিফায়ারের ডিসিশন (Malicious বনাম Safe) বনাম গ্রাউন্ড ট্রুথ।
   - **Table B (Enforcement Breakdown):** কতগুলো রিকোয়েস্ট Explicit Block (429) হয়েছে, কতগুলো Step-up Challenge (428) পেয়েছে, কতগুলো চ্যালেঞ্জ পূরণ হয়েছে, আর কতগুলো চ্যালেঞ্জে আটকে গেছে।
2. **ফলস পজিটিভের নমুনা সংখ্যা বাড়ানো:**
   `php artisan security:evaluate --legit-users=200` দিয়ে সাধারণ ইউজারদের টাইপো, পাসওয়ার্ড ভুলে যাওয়া ইত্যাদি সিমুলেশন বাড়িয়ে অন্তত ২০০-৫০০ বৈধ রিকোয়েস্টের উপর টেস্ট ফলাফল প্রকাশ করা।

---

## Comment 6: Controlled Baseline Comparison and MCP-Specific Ablation

### রিভিউয়ারের মূল মন্তব্য:
> *"Table 5 places the proposed method alongside traditional Laravel throttling and Redis-only protection, but the paper acknowledges that the baseline latency values are indicative rather than measurements under the same harness. This prevents a controlled comparison of security effectiveness and overhead. The authors should evaluate all approaches on identical traffic traces and compute comparable recall, false-positive rate, throughput, and latency statistics."*

### সমস্যার মূল কারণ:
Table 5 এ লারাভেল ও রেডিস বেসলাইনের যে ল্যাটেন্সি দেওয়া ছিল তা আনুমানিক (indicative) ছিল, একই টেস্টবেডে মাপা হয়নি।

### যা যা করতে হবে (Step-by-Step Action):
1. **একই ট্রাফিক ট্রেসে তুলনামূলক বেঞ্চমার্ক তৈরি:**
   একটি টেস্ট স্ক্রিপ্ট দিয়ে একই ১০০টি বা ৫০০টি রিকোয়েস্ট নিচের ৪টি মোডে পাঠিয়ে মেট্রিক্স মাপতে হবে:
   - Mode 1: Traditional Laravel Throttling (RateLimiter)
   - Mode 2: Redis-Only Fixed Window Counter
   - Mode 3: Deterministic Rule Scoring (No AI)
   - Mode 4: HySA-MCP (Full Proposed System)
2. **তুলনামূলক টেবিল আপডেট:**
   | মেথড | Precision | Recall | FPR | F1-Score | Avg Latency (ms) | Throughput (req/s) |
   |---|---|---|---|---|---|---|
   | Laravel Native | ... | ... | ... | ... | ... | ... |
   | Redis-Only | ... | ... | ... | ... | ... | ... |
   | Rule-Based | ... | ... | ... | ... | ... | ... |
   | **HySA-MCP (Ours)**| ... | ... | ... | ... | ... | ... |

---

## Comment 7: End-to-End Latency and Adversarial Resource Exhaustion

### রিভিউয়ারের মূল মন্তব্য:
> *"The measured AI-call latency (approximately 2.6–4.4 seconds) is substantial for an interactive login path, while the reported 8 ms figure covers only the isolated Redis fast path. The authors should benchmark end-to-end login latency under concurrency, reporting p50/p95/p99, throughput, timeout rates, retry amplification, and behavior under upstream-model outages."*

### সমস্যার মূল কারণ:
স্বাভাবিক ইউজার ৮ মিলিমিটারের ফাস্ট-পাথ পেলেও, এআই কল হলে ২.৬ থেকে ৪.৪ সেকেন্ড সময় লাগে। যদি অনেকগুলো অ্যাটাক একসাথে আসে, তবে এআই এপিআই কল জমে গিয়ে পুরো সার্ভার ক্র্যাশ করতে পারে কি না (Denial of Service), তার কোনো কনকারেন্ট টেস্ট পেপারে ছিল না।

### কোডের বর্তমান অবস্থা:
- কোডে `MCP_TIMEOUT=10` এবং `MCP_RETRIES=1` সেট করা আছে।
- AI সার্ভার ডাউন থাকলে টিয়ার্ড ফেইলওভার (Problem 2 fix) কার্যকর হয়ে লোকাল রুলস দিয়ে সাথে সাথে সিদ্ধান্ত দেয়।

### যা যা করতে হবে (Step-by-Step Action):
1. **ল্যাটেন্সি বেঞ্চমার্ক রান করা:**
   স্বাভাবিক ফাস্ট পাথ বনাম এআই এসকেলেশন পাথের p50, p95, p99 ল্যাটেন্সি পরিমাপ করা।
   - ফাস্ট-পাথ (Fast-path): Redis check $\to$ ~5-15 ms.
   - স্লো-পাথ (AI escalation): MCP/LLM call $\to$ ~800-1500 ms (জেমিনি ফ্ল্যাশের ক্ষেত্রে আরও দ্রুত)।
2. **কনকারেন্সি ও রিসোর্স এক্সহশন ডিফেন্স ব্যাখ্যা:**
   পেপারে স্পষ্ট করতে হবে যে, আক্রমণকারী ডস (DoS) করতে চাইলে প্রথমবার এআই কল হয়ে আইপি ব্লক বা এমার্জেন্সি থ্রটল হয়ে যাওয়ার পর পরবর্তী রিকোয়েস্টগুলো **এআই-তে যাওয়ার আগেই রেডিস ফাস্ট-পাথ থেকে ১ মিলিসেকেন্ডে ড্রপ (Drop) হয়ে যায়**। ফলে আপস্ট্রিম এআই সুরক্ষিত থাকে।

---

## Comment 8: Adversarial Manipulation of NAT-Aware Blocking

### রিভিউয়ারের মূল মন্তব্য:
> *"The NAT-aware defense switches from IP-scoped to email-scoped blocking when at least three distinct accounts have authenticated successfully from an IP address. An adversary with access to multiple valid accounts might deliberately establish this condition and then rotate target email addresses, weakening source-level blocking. The authors should evaluate whether the successful-account threshold is susceptible to such manipulation, how long shared-IP status persists, and how it behaves under attacker-controlled account churn. The security–availability trade-off should be quantified across residential, enterprise-NAT, and proxy scenarios."*

### সমস্যার মূল কারণ:
আমাদের সিস্টেমে ৩টি সফল লগইন হলে ওই আইপিকে NAT বা শেয়ার্ড আইপি মনে করে পুরো আইপি ব্লক না করে শুধু সংশ্লিষ্ট ইমেইল ব্লক করা হয়। রিভিউয়ার ধরেছেন— অ্যাটাকার যদি ৩টি আসল একাউন্টে পাসওয়ার্ড দিয়ে আগে লগইন করে নেয়, তবে সিস্টেম ভাববে এটি NAT আইপি। এরপর ওই আইপি থেকে অন্য সব একাউন্টে ব্রুটফোর্স চালালে আইপি ব্লক হবে না!

### কোডের বর্তমান অবস্থা:
- `app/Services/McpSecurityService.php` এর `isSharedIp()` মেথড এবং `McpNatMitigationTest.php` এটি হ্যান্ডেল করে।

### যা যা করতে হবে (Step-by-Step Action):
1. **অ্যাটাক সিনারিও বিশ্লেষণ ও থ্রেট মডেলে সংযোজন:**
   এই আক্রমণটিকে "NAT-Defense Poisoning Attack" হিসেবে চিহ্নিত করে এর বিরুদ্ধে বর্তমান সেফগার্ড ব্যাখ্যা করতে হবে:
   - সফল একাউন্টের সেটটি চিরস্থায়ী নয়, ফিক্সড উইন্ডো টিটিএল (`ATTEMPT_WINDOW_SECONDS = 900s`) এর পর স্বয়ংক্রিয়ভাবে এক্সপায়ার হয়ে যায়।
   - ইমেইল ব্লক হলেও যদি ওই আইপি থেকে মোট অ্যাটেম্পট সংখ্যা চরম পর্যায়ে পৌঁছায় (Extreme Burst, e.g., $\ge 50$ attempts), তবে হার্ড গার্ডরেইল পুরো আইপিকেই ব্লক করে।
2. **সিকিউরিটি বনাম অ্যাভেইলেবিলিটি ট্রেড-অফ চার্ট:**
   একটি টেবিল দেওয়া যা দেখাবে:
   - রেসিডেন্সিয়াল মোড (কড়া আইপি ব্লক)
   - এন্টারপ্রাইজ NAT মোড (ব্যালান্সড একাউন্ট ব্লক)
   - প্রক্সি/VPN মোড (ডিগ্রেডেড মোড)।
