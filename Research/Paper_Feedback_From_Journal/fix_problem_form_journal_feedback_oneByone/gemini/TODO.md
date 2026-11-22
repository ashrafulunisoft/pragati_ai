# 📋 Research Paper Revision & Reviewer Feedback — TODO Tracking

**Target Journal:** *International Journal of Information Security (IJIS)* — **Springer Nature**  
**Manuscript Title:** *HySA-MCP: A Hybrid AI-Driven Secure Authentication Framework with Model Context Protocol (MCP) Integration*  
**Submission ID:** `c372b849-6b41-418e-ad06-5c08e86be13a`  
**Current Journal Decision:** **Major Revision (Revise and Resubmit)**  
**Last Updated:** 2026-11-19  

---

## 📊 সামগ্রিক অগ্রগতি ড্যাশবোর্ড (Overall Progress)

| বিভাগ | মোট কাজ | সম্পন্ন | চলমান | বাকি | অগ্রগতি (%) |
|---|:---:|:---:|:---:|:---:|:---:|
| **Reviewer Comments (1–8)** | ৮ | ৮ | ০ | ০ | **১০০%** |
| **Experimental Evaluations** | ৪ | ৪ | ০ | ০ | **১০০%** |
| **Manuscript Deliverables** | ২ | ০ | ২ | ০ | **০%** |
| **সর্বমোট** | **১৮** | **১৪** | **২** | **২** | **৭৮%** |

---

## 🎯 রিভিউয়ারের ৮টি পয়েন্টের অগ্রগতি (Reviewer Feedback Progress)

### [x] Task 1: Validation of LLM-Driven Detection Gains (Comment 1)
- [x] **1.1** ইভালুয়েশন হারনেসে `--ai=live` অপশন ও লাইভ এপিআই ড্রাইভার প্রস্তুতকরণ (`SecurityEvaluateCommand.php`)
- [x] **1.2** গুগল জেমিনি এআই এপিআই লাইভ কানেকশন ও কোটা সক্রিয়করণ (`.env` configured, `gemini-3.1-flash-lite`)
- [x] **1.3** একাধিক সীডে (`seed=42, 7, 123, 999, 2026`) লাইভ এআই ইভালুয়েশন রান করা ও কনফিউশন ম্যাট্রিক্স সংগ্রহ (প্রকৃত পরিমাপ: Mean Recall 70.79% ± 8.77%, Accuracy 78.38% ± 6.73%, F1 0.8264 ± 0.0625, FP 0/232; eval-live-multi-20261119_100355)
- [x] **1.4** সিস্টেম প্রম্পট ও JSON-RPC ২.০ স্কিমা পেপারের সেকশন/অ্যাপেন্ডিক্সে যুক্ত করা (Appendix A তৈরি)
- [x] **1.5** সিমুলেটেড ফলাফলের পাশাপাশি লাইভ মডেলের ফলাফল টেবিল ৬-এ আপডেট করা (Table 6a ও Table 6b প্রস্তুত)
> **Status:** 🟢 *Completed (কোড, লাইভ রান ও ডকুমেন্টেশন সম্পন্ন)*

---

### [x] Task 2: Detection Gap Under Joint IP & Account Rotation (Comment 2)
- [x] **2.1** থ্রেট মডেলে লো-এন্ড-স্লো ও বিলো-থ্রেশহোল্ড অ্যাটাক বাউন্ডারি বিশ্লেষণ সম্পন্ন (`THREAT_MODEL.md` §7.2)
- [x] **2.2** পেপারের সেকশন ৪.১ এবং সেকশন ৬ এ স্পষ্টভাবে লিমিটেশন ও কমপেনসেটিং কন্ট্রোল (MFA, Subnet counter) স্বীকৃতি প্রদান
- [x] **2.3** থ্রেশহোল্ডের নিচে রোটেট করলে এআই ইনভোকেশন যে ০% তা পরিমাপ (`below_threshold_rotation` সিনারিওতে Recall 0%, AI Never Invoked 100.0%)
- [x] **2.4** পূর্বের `distributed_stuffing`-এ ৯০% রিকল হওয়ার সঠিক কারণ প্রকাশ (প্রতি অ্যাকাউন্টে ৩০টি ট্রাই $\ge 3$)
- [x] **2.5** নতুন ইউনিট টেস্ট স্যুট সংযোজন ও যাচাই (`McpBelowThresholdRotationTest.php` ৩টি টেস্ট পাস)
- [x] **2.6** স্প্রিংগার জার্নাল পেপারের Table 7 এ Attack H যোগ, Section 4.1, 5.4, 6 আপডেট এবং Paste-Ready Rebuttal Letter তৈরি
> **Status:** 🟢 *Completed (কোড, টেস্ট, মেজারমেন্ট ও ডকুমেন্টেশন সম্পূর্ণ)*

---

### [x] Task 3: Risk-Score Fusion & Challenge Consistency (Comment 3)
- [x] **3.1** কোডে $S = \max(S_{\text{rule}}, S_{\text{AI}})$ ফিউশন লজিক (F1–F5) বাস্তবায়ন ও টেস্ট পাস (`McpDecisionFusionTest.php` ৯টি টেস্ট পাস)
- [x] **3.2** মিডলওয়্যারে SUSPICIOUS হলে সরাসরি **HTTP 428 Precondition Required** রিটার্ন করে রিকোয়েস্ট থামানো নিশ্চিতকরণ (`McpChallengeEnforcementTest.php` ১১টি টেস্ট পাস)
- [x] **3.3** পেপারের **Algorithm 1 ও Algorithm 2 এর সিউডোকোড পুনর্লিখন** (কোডের বাস্তব লজিকের সাথে মিল রেখে $S \leftarrow \max(S_{rule}, S_{AI})$, $S_{AI}$ বাউন্ডিং এবং টার্মিনাল ৪২৮ চ্যালেঞ্জ এনফোর্সমেন্ট সহ প্রস্তুত)
- [x] **3.4** পেপারের সেকশন ৪.৩ ও ৪.৪-এ State-Transition Specification এবং Table 2 তে Enforced HTTP Outcome (403, 428, 429, 200) যোগ করা
- [x] **3.5** স্প্রিংগার জার্নাল রেসপন্স লেটার প্রস্তুত ও ১১৪টি টেস্টের মধ্যে ১১৪টিই পাস
> **Status:** 🟢 *Completed (কোড, টেস্ট, পেপার অ্যালগরিদম রিরাইট ও ডকুমেন্টেশন সম্পূর্ণ)*

---

### [x] Task 4: Semantics & Timing of Redis Failure Counters (Comment 4)
- [x] **4.1** প্রি-অথেনটিকেশন কাউন্টার ও সফল লগইনে ইমেইল কাউন্টার রিসেট লজিক কোডে কার্যকর (`LoginController.php`)
- [x] **4.2** কাউন্টার লাইফসাইকেল (Confirmed Failure বনাম All Requests) এর পরিষ্কার Update Sequence Table পেপারে যুক্ত করা (Section 4.1, Markdown Table)
- [x] **4.3** বৈধ ইউজারের পুনঃপুন সফল লগইনে কাউন্টার জমা না হওয়ার ডেডিকেটেড টেস্ট স্যুট তৈরি ও পাস (`McpCounterSemanticsTest.php` ৪টি টেস্ট পাস, সর্বমোট ১১৮টি পাস)
- [x] **4.4** Listing 1 ও Figure 1 এর টাইমিং অ্যানোটেশন (Read-before-attempt বনাম Write-after-failure) পেপারে সংযোজন ও Rebuttal Letter ড্রাফট তৈরি
> **Status:** 🟢 *Completed (কোড, টেস্ট, পেপার টেবিল ও ডকুমেন্টেশন সম্পূর্ণ)*

---

### [x] Task 5: Threat Detection বনাম Challenge-Based Prevention পৃথকীকরণ (Comment 5)
- [x] **5.1** মিডলওয়্যার ও ইভালুয়েশন হারনেসে pure classification decision (`X-Mcp-Decision`) এবং enforcement outcome (`X-Mcp-Enforcement`: block, challenge, allow) আলাদা করা
- [x] **5.2** মাল্টি-সীড পুলে মোট ২৩২টি বৈধ নেগেটিভ লগইন যাচাই এবং ৯৫% কনফিডেন্স ইন্টারভাল (Wilson Score ও Rule of Three: n=49 এ 0–6.1%, pooled N=232 এ 0–1.3%) পরিমাপ
- [x] **5.3** পেপারের রেজাল্ট সেকশনে Table 6a (Threat Classification) ও Table 6b (Enforcement Breakdown) আলাদা করে দেখানো
- [x] **5.4** নতুন ডেডিকেটেড টেস্ট স্যুট তৈরি (`McpDetectionVsEnforcementTest.php`, ৫টি টেস্ট পাস, মোট ১২৩টি টেস্ট পাস)
- [x] **5.5** রিভিশন পেপার ড্রাফট ও স্প্রিংগার জার্নাল রেসপন্স লেটার প্রস্তুত (`update research paper.md` Entry 05 সম্পন্ন)
> **Status:** 🟢 *Completed (কোড, হার্নেস, টেস্ট, টেবিল ৬a/৬b ও ডকুমেন্টেশন সম্পূর্ণ)*

---

### [x] Task 6: Controlled Baseline Comparison & MCP Ablation (Comment 6)
- [x] **6.1** একই ট্রাফিক ডেটাসেটে ৪টি বেসলাইন চালানোর সাপোর্ট তৈরি (`--engine=hysa|laravel_throttle|redis_only|rule_based`)
- [x] **6.2** প্রতিটি বেসলাইনের বাস্তব Recall, Precision, FPR, Latency (mean, p50, p95, p99) এবং Throughput পরিমাপ সম্পন্ন
- [x] **6.3** পেপারের Table 5-এ আনুমানিক সংখ্যার বদলে অভিন্ন ট্রেসের এই বাস্তব তুলনামূলক মেট্রিক্স প্রতিস্থাপন (`update research paper.md` Entry 06)
- [x] **6.4** মডেল কনটেক্সট প্রোটোকল (MCP) ট্রান্সপোর্ট লেয়ার অ্যাবলেশন সম্পন্ন (`driver=direct` বনাম `driver=mcp` ওভারহেড টেবিল প্রস্তুত)
- [x] **6.5** নতুন ডেডিকেটেড টেস্ট স্যুট তৈরি (`McpControlledBaselineTest.php`, ৫টি টেস্ট পাস, মোট ১২৮টি টেস্ট পাস)
> **Status:** 🟢 *Completed (কোড, টেস্ট, পেপার টেবিল ৫/৫b ও ডকুমেন্টেশন সম্পূর্ণ)*

---

### [x] Task 7: End-to-End Latency & Concurrency Benchmarking (Comment 7)
- [x] **7.1** ফাস্ট-পাথ (Redis-only ~1.4ms) বনাম স্লো-পাথ (Live Gemini ~1.9s) এর p50, p95, p99 ল্যাটেন্সি পরিমাপ করা (`php artisan security:benchmark`)
- [x] **7.2** মাল্টি-প্রসেস কনকারেন্ট টেস্ট ($C \in \{1, 10, 50, 100\}$) করে থ্রুপুট (req/s), টাইমআউট রেট এবং রিট্রাই অ্যামপ্লিফিকেশন পরিমাপ
- [x] **7.3** DoS ও রিসোর্স এক্সহশনের বিরুদ্ধে আর্কিটেকচারাল সুরক্ষা (ব্লকড আইপি এআই কলের আগেই রেডিস লেভেলে $\le 1\text{ ms}$-এ ড্রপ হওয়া) ও ৩টি মিটিগেশন (Pre-analysis CAPTCHA, Async Queue, Threat Caching) পেপারে সংযোজিত
- [x] **7.4** নতুন ডেডিকেটেড টেস্ট স্যুট তৈরি (`McpConcurrencyLatencyTest.php`, ৫টি টেস্ট পাস, মোট ১৩৩টি টেস্ট পাস)
- [x] **7.5** পেপারের Table 8, Section 5.3, Section 5.6 ও Rebuttal Letter ড্রাফট সম্পন্ন (`update research paper.md` Entry 07)
> **Status:** 🟢 *Completed (কোড, কনসোল টুলিং, টেস্ট, পেপার টেবিল ৮ ও ডকুমেন্টেশন সম্পূর্ণ)*

---

### [x] Task 8: Adversarial Manipulation of NAT-Aware Mitigation (Comment 8)
- [x] **8.1** কোডে ৩টি সফল অ্যাকাউন্টের শর্ত ও ইমেইল-স্কোপড ব্লকিং তৈরি (`McpNatMitigationTest.php`)
- [x] **8.2** আক্রমণকারী ৩টি ভ্যালিড একাউন্ট দিয়ে পয়জনিং করলে কী হবে তার সিকিউরিটি বাউন্ডারি পেপারে যোগ করা (`nat_gaming` সিনারিও, Table 9)
- [x] **8.3** ফিক্সড উইন্ডো টিটিএল (৯০০ সেকেন্ড) এবং চরম আক্রমণ অবস্থায় গ্লোবাল আইপি ব্লকিং গার্ডরেইল পেপারে স্পষ্টভাবে লেখা (`MCP_SHARED_IP_MAX_BLOCKED_ACCOUNTS = 5`, `MCP_SHARED_IP_MAX_IP_ATTEMPTS = 25`)
- [x] **8.4** নতুন টেস্ট স্যুট তৈরি (`McpNatGamingTest.php`, ৬টি টেস্ট পাস, সর্বমোট ১৩৯টি টেস্ট গ্রিন)
- [x] **8.5** পেপারের Table 9, Section 4.4, Section 6.2 ও Rebuttal Letter ড্রাফট সম্পন্ন (`update research paper.md` Entry 08)
> **Status:** 🟢 *Completed (কোড, টেস্ট, হার্নেস অ্যাটাক সিনারিও ও পেপার টেবিল ৯ সম্পূর্ণ)*

---

## 📝 এডিটর নির্দেশিকা ও পেপার ডেলিভারেবলস (Deliverables)

- [ ] **D1: Marked-Up Manuscript (Updated PDF/LaTeX)**
  - [ ] Abstract থেকে "১০০% নিরাপদ" জাতীয় অতি-দাবি বাদ দেওয়া
  - [ ] Algorithm 1 ও Algorithm 2 আপডেট করা
  - [ ] Table 5 ও Table 6 নতুন লাইভ ও বেঞ্চমার্ক ডেটা দিয়ে আপডেট করা
  - [ ] সেকশন ৬-এ Limitations সেকশন যুক্ত করা
  - [ ] পরিবর্তিত অংশগুলো নীল রঙে হাইলাইট করা
- [ ] **D2: Point-by-Point Rebuttal Document (Response to Reviewers)**
  - [ ] এডিটরের মন্তব্যের আনুষ্ঠানিক উত্তর
  - [ ] রিভিউয়ারের Comment 1 থেকে 8 এর সুনির্দিষ্ট উত্তর ও পেপার রেফারেন্স

---

## 📌 পরবর্তী তাৎক্ষণিক পদক্ষেপ (Next Action Item):
👉 **Task 1.3 শুরু করা:** `php artisan security:evaluate --ai=live --seed=42` দিয়ে প্রথম লাইভ ইভালুয়েশন রান করে বাস্তব ডেটা সংগ্রহ করা।
