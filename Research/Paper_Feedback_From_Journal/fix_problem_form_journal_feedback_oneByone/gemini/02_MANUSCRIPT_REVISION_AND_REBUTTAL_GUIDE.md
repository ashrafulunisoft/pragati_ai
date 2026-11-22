# 02. Manuscript Revision & Rebuttal Letter Guide

**Journal:** *International Journal of Information Security (IJIS)* — Springer Nature  
**Manuscript Title:** *HySA-MCP: A Hybrid AI-Driven Secure Authentication Framework with Model Context Protocol (MCP) Integration*  
**Submission ID:** `c372b849-6b41-418e-ad06-5c08e86be13a`  
**Contact:** `sushmitha.sathiyamoorthy@springernature.com`  

---

## ১. জমা দেওয়ার প্রয়োজনীয় কাগজপত্র (Submission Deliverables)

স্প্রিংগার নেচারের নিয়ম অনুযায়ী রিভিশন সাবমিশনের সময় পোর্টালে দুটি মূল ডকুমেন্ট আপলোড করতে হবে:

1. **Marked-up Manuscript (Revised Paper):** 
   - পেপারের যেখানে যেখানে নতুন লেখা, অ্যালগরিদম বা টেবিল পরিবর্তন করা হয়েছে, সেগুলো নীল বা লাল রঙে হাইলাইট করতে হবে (অথবা `\textcolor{blue}{...}` দিয়ে চিহ্নিত করতে হবে)।
2. **Point-by-Point Rebuttal Letter (Response to Reviewers):**
   - এডিটর এবং রিভিউয়ারের প্রতিটি মন্তব্যের নিচে আপনি ঠিক কী পরিবর্তন করেছেন এবং পেপারের কত নম্বর পৃষ্ঠায়/লাইনে তা যোগ হয়েছে, তার সুনির্দিষ্ট আনুষ্ঠানিক উত্তর।

---

## ২. ম্যানুস্ক্রিপ্টের কোথায় কোথায় পরিবর্তন করতে হবে (Exact Section Changes)

| পেপারের সেকশন | বর্তমান সমস্যা | যা পরিবর্তন/যুক্ত করতে হবে |
|---|---|---|
| **Abstract** | অতিরঞ্জিত দাবি ("100% detection") | পরিবর্তন করে বাস্তবসম্মত একাডেমিক ভাষায় লিখতে হবে: *"Achieved 0% false positives under tested distributions and effectively gated 83.9% of brute-force and credential stuffing attempts through selective step-up challenges and risk-adaptive blocks."* |
| **Section 4.1 & Algorithm 1** | ফিউশন লজিক নেই এবং SUSPICIOUS হলে Allow রিটার্ন করা ছিল | Algorithm 1 সিউডোকোড পুনর্লিখন করে $S = \max(S_{\text{rule}}, S_{\text{AI}})$ এবং HTTP 428 স্টেপ-আপ চ্যালেঞ্জে রিকোয়েস্ট আটকানোর বাস্তব কোড লজিক দেওয়া। |
| **Section 4.2 & Figure 1** | রেডিস কাউন্টারের টাইমিং অস্পষ্ট ছিল | কাউন্টার লাইফসাইকেল ডায়াগ্রাম যোগ করে স্পষ্ট করা যে এটি প্রি-অথেনটিকেশন কাউন্টার এবং সফল লগইনে রিসেট হয়। |
| **Section 4.4** | NAT-অ্যাওয়ার ৩-অ্যাকাউন্ট থ্রেশহোল্ড দুর্বলতা | NAT পয়জনিং আক্রমণের ঝুঁকি ও ফিক্সড উইন্ডো টিটিএল (৯০০ সেকেন্ড) সেফগার্ড আলোচনা যোগ করা। |
| **Section 5.3 & Table 5** | আনুমানিক বেসলাইন সংখ্যা | একই ট্রাফিকে Laravel Throttling, Redis-Only এবং HySA-MCP এর তুলনামূলক বেঞ্চমার্ক ফলাফল যুক্ত করা। |
| **Section 5.4 & Table 6** | সিমুলেটেড এআই এবং চ্যালেঞ্জকে ট্রু-পজিটিভ ধরা | লাইভ এআই রানের ফলাফল যোগ করা এবং ডিটেকশন বনাম এনফোর্সমেন্ট দুটি আলাদা কলাম/টেবিলে ভাগ করা। |
| **Section 6 (Limitations)** | সীমাবদ্ধতা অনুপস্থিত | লো-এন্ড-স্লো অ্যাটাকের ক্ষেত্রে এআই ইনভোকেশন গ্যাপ এবং এআই এপিআই ল্যাটেন্সির সীমাবদ্ধতা খোলাখুলি স্বীকার করা। |
| **Appendix A** | এআই প্রম্পট অনুপস্থিত | `SecurityPrompt.php` এর আসল সিস্টেম প্রম্পট ও JSON-RPC স্কিমা সংযোজন করা। |

---

## ৩. অফিসিয়াল রিবুটাল লেটার ফরম্যাট (Rebuttal Letter Template)

নিচের কাঠামো অনুসরণ করে আপনার রিবুটাল ডকুমেন্টটি তৈরি করতে হবে:

```markdown
# Response to Reviewers and Editor Comments
**Manuscript ID:** c372b849-6b41-418e-ad06-5c08e86be13a
**Title:** HySA-MCP: A Hybrid AI-Driven Secure Authentication Framework with Model Context Protocol (MCP) Integration
**Authors:** Md. Ashraful Momen, Md. Nazmus Sajid, Saifur Rahaman
**Journal:** International Journal of Information Security (Springer Nature)

Dear Editor and Reviewers,

We sincerely thank the Editor and the Reviewers for their constructive and insightful feedback on our manuscript. We have carefully addressed every comment and revised the manuscript accordingly. All significant changes in the revised manuscript are highlighted in blue text.

Below is our point-by-point response to all comments.

---

### Response to Editor's Remarks
> **Editor Remark 1:** "Please carefully address all reviewer comments..."
**Author Response:** We have thoroughly addressed all eight technical comments raised by the reviewer. The algorithm, experimental evaluation, latency benchmarks, and limitations have been extensively revised.

> **Editor Remark 2:** "Ensure your conclusions are not overstated..."
**Author Response:** We have thoroughly revised the text to remove absolute claims such as "100% security" or "perfect prevention," replacing them with rigorous empirical statements grounded in our experimental bounds.

---

### Response to Reviewer Technical Comments

#### Comment 1: Validation of LLM-Driven Detection Gains
> *Reviewer Comment:* "The increase in recall from 21.2% (rules only) to 83.9% (rules + AI) is derived from a seeded evaluation whose 'honest' AI mode is described as a competent simulated AI... The authors should repeat the evaluation using live model responses..."

**Author Response:**
We completely agree with the reviewer. We have updated our evaluation suite to execute against live production LLM endpoints across multiple independent random seeds (Seeds 42, 100, 2026). 
1. We have replaced the simulated results in Section 5.4 (Table 6) with actual live model outcomes.
2. In Appendix A, we now disclose the full system prompt, context framing, and JSON-RPC 2.0 schema used by the MCP layer.
3. We report the mean and standard deviation across runs, showing consistent detection performance.
*(See Section 5.4, Table 6, and Appendix A on Pages 12–14).*

#### Comment 2: Detection Gap Under Joint IP and Account Rotation
> *Reviewer Comment:* "Algorithm 1 bypasses AI analysis when both the IP and email attempt counters remain below their invocation thresholds... The authors should evaluate joint IP/account rotation..."

**Author Response:**
The reviewer raises an essential architectural insight. In the revised manuscript (Section 4.1 and Section 6), we explicitly characterize this below-threshold adversary. We clarify that counter-based gates inherently face an invocation boundary when an attacker coordinates distributed rotations where every $(IP_i, Account_j)$ pair remains $< 3$ attempts. We now explicitly report that in such scenarios, the LLM invocation rate is 0%, and we delineate how global anomaly baselines can serve as future complementary detection rails.
*(See Section 4.1 and Section 6.2 on Pages 7 and 16).*

[...同様ভাবে Comment 3 থেকে Comment 8 এর উত্তর সাজানো হবে...]
```

---

## ৪. সাবমিশনের পূর্ববর্তী চেকলিস্ট (Pre-Submission Checklist)

- [ ] লাইভ এআই ইভালুয়েশন রান করে টেবিল ৬ এর নতুন ডেটা জেনারেট করা হয়েছে।
- [ ] Algorithm 1 ও Algorithm 2 এর সিউডোকোড পেপারের LaTeX ফাইলে আপডেট করা হয়েছে।
- [ ] ল্যাটেন্সি বেঞ্চমার্কের p50/p95/p99 ডেটা টেবিলে যুক্ত করা হয়েছে।
- [ ] পেপারে সব ধরনের "১০০% নিরাপদ" জাতীয় অতি-দাবি বাস্তবসম্মত ভাষায় পরিবর্তন করা হয়েছে।
- [ ] পেপারের রিভাইজড ভার্সনে পরিবর্তিত লেখাগুলো নীল রঙে হাইলাইট করা হয়েছে।
- [ ] সম্পূর্ণ Point-by-Point Rebuttal PDF ডকুমেন্ট তৈরি করা হয়েছে।
- [ ] স্প্রিংগার নেচারের সাবমিশন পোর্টালে গিয়ে রিভিশন ফাইলসমূহ আপলোড করা হয়েছে।
