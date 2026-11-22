# 00. Executive Summary & Current Project Status

**Target Journal:** *International Journal of Information Security (IJIS)* — **Springer Nature**  
**Paper Title:** *HySA-MCP: A Hybrid AI-Driven Secure Authentication Framework with Model Context Protocol (MCP) Integration*  
**Submission ID:** `c372b849-6b41-418e-ad06-5c08e86be13a`  
**Editorial Decision:** **Major Revision (Revise and Resubmit)**  
**Document Generated:** 2026-11-19  

---

## ১. প্রজেক্টের বর্তমান বাস্তব অবস্থা (Current Live State)

সিস্টেমের অবকাঠামো, ডেটাবেজ, ব্যাকএন্ড সার্ভিস এবং সিকিউরিটি কোডের বর্তমান অবস্থা নিচে সম্পূর্ণ বাস্তব তথ্যের ভিত্তিতে তুলে ধরা হলো:

### ক. লাইভ সার্ভিস ও ব্যাকএন্ড ইনফ্রাস্ট্রাকচার
- **লারাভেল ফ্রেমওয়ার্ক:** Laravel 12.48.1 (PHP 8.4.16 CLI)
- **PM2 প্রসেস ম্যানেজার (Online & Active):**
  - `pragati-ai-web` (ID 0): সচল (Port `8000`), HTTP 200 OK রেসপন্স দিচ্ছে।
  - `pragati-ai-queue` (ID 1): সচল (Background queue worker সচল)।
- **ডকার কন্টেইনার (Up & Running):**
  - `pragati_ai_mysql`: Port `127.0.0.1:3307->3306` (MySQL 8.0, DB: `pragati_ai_db_2`, 28টি টেবিল সক্রিয়)।
  - `pragati_ai_redis`: Port `127.0.0.1:6380->6379` (Redis 7 Alpine, PING: PONG)।
  - `pragati_ai_phpmyadmin`: Port `0.0.0.0:8080->80` (phpMyAdmin ওয়েব প্যানেল সচল)।
- **লাইভ এআই কানেকশন:**
  - Google Gemini API (`gemini-3.1-flash-lite`) সফলভাবে `.env` তে ইন্টিগ্রেট করা হয়েছে।
  - সম্পূর্ণ ফ্রি কোটায় (দিনে ১,৫০০ রিকোয়েস্ট) বাংলা ও ইংরেজিতে তাৎক্ষণিক রেসপন্স করছে।

### খ. ইউনিট টেস্ট ও অটোমেশন স্ট্যাটাস
- **ইউনিট টেস্ট স্ট্যাটাস:** **১০৬টি টেস্টের মধ্যে ১০৬টিই পাস (106/106 Passed, 364 assertions, ~2.8s)**।
  - `McpAtomicRedisTest` (5 passed) — Lua স্ক্রিপ্টেড অ্যাটমিক ফিক্সড-উইন্ডো ও টিটিএল সেলফ-হিলিং।
  - `McpChallengeEnforcementTest` (10 passed) — HTTP 428 স্টেপ-আপ চ্যালেঞ্জ (OTP/Math Captcha)।
  - `McpConfigurableThresholdsTest` (6 passed) — রানটাইম কনফিগারেবল থ্রেশহোল্ড।
  - `McpDecisionFusionTest` (9 passed) — F1–F5 রিস্ক ফিউশন ম্যাট্রিক্স (AI can raise, never lower floor)।
  - `McpFailoverPolicyTest` (6 passed) — টিয়ার্ড ফেইলওভার (AI ডাউন হলেও deterministic guardrail কার্যকর)।
  - `McpNatMitigationTest` (5 passed) — NAT-অ্যাওয়ার ইমেইল-স্কোপড ব্লকিং।
  - `McpPrivacyPseudonymizationTest` (7 passed) — HMAC-SHA256 ছদ্মনামকরণ (No PII to LLM)।
  - `McpPromptInjectionTest` (10 passed) — ৪ স্তরের প্রম্পট ইনজেকশন ডিফেন্স।
  - `McpProtocolTest` (12 passed) — অফিসিয়াল Model Context Protocol (Streamable HTTP, JSON-RPC 2.0)।
  - `McpSecurityLoginTest` (36 passed) — এন্ড-টু-এন্ড সিকিউরিটি পাইপলাইন টেস্ট।

### গ. ইভালুয়েশন হারনেস (Security Evaluation Harness)
- `php artisan security:evaluate` কমান্ডটি তৈরি আছে যা ৪টি মোডে চলতে পারে:
  1. `--ai=off` (শুধুমাত্র রুলস ও রেডিস কাউন্টার)
  2. `--ai=honest` (কম্পিটেন্ট সিমুলেটেড এআই)
  3. `--ai=adversarial` (কম্প্রোমাইজড এআই / প্রম্পট ইনজেকশন সিনারিও)
  4. `--ai=live` (সরাসরি লাইভ এআই এপিআই কল)

---

## ২. জার্নাল রিভিউর মূল চ্যালেঞ্জ ও করণীয় (Journal Feedback Gap)

স্প্রিংগার নেচারের IJIS জার্নাল থেকে পেপারটি রিজেক্ট না করে **মেজর রিভিশন (Major Revision)** দেওয়া হয়েছে। রিভিউয়ারের ৮টি টেকনিক্যাল পয়েন্ট ও এডিটরের রিকোয়ারমেন্ট অনুযায়ী যা যা ঠিক করতে হবে তার সারসংক্ষেপ:

| পয়েন্ট # | মূল বিষয় | পেপারের অবস্থা | কোডের অবস্থা | করণীয় কাজ |
|---|---|---|---|---|
| **Comment 1** | লাইভ মডেল ভ্যালিডেশন | সিমুলেটেড এআই রেজাল্ট দেওয়া | লাইভ মোড রেডি | লাইভ এআই দিয়ে টেস্ট রান করে নতুন কনফিউশন ম্যাট্রিক্স ও ভ্যারিয়েন্স পেপারে বসানো। |
| **Comment 2** | জয়েন্ট আইপি ও একাউন্ট রোটেশন ফাঁক | দাবি করা হয়েছে ৯০% আটকায় | থ্রেশহোল্ডের নিচে রোটেট করলে এআই কল হয় না | পেপারে স্বীকার করা এবং বাউন্ডেড লিমিটেশন হিসেবে থ্রেট মডেলে ব্যাখ্যা করা। |
| **Comment 3** | ফিউশন লজিক ও অ্যালগরিদম ১ অসঙ্গতি | Alg 1 এ ফিউশন নেই এবং Allow রিটার্ন করে | কোডে F1–F5 ফিউশন ও HTTP 428 চ্যালেঞ্জ এনফোর্সড | পেপারের সিউডোকোড (Algorithm 1 ও 2) কোডের সাথে হুবহু আপডেট করা। |
| **Comment 4** | রেডিস কাউন্টারের টাইমিং | কাউন্টার কি চেষ্টার নাকি ব্যর্থতার? | প্রি-অথ চেষ্টা কাউন্ট হয়, সফল হলে ইমেইল কাউন্টার রিসেট হয় | স্টেট ট্রানজিশন ডায়াগ্রাম ও স্পষ্ট লাইফসাইকেল বর্ণনা যুক্ত করা। |
| **Comment 5** | থ্রেট ডিটেকশন বনাম এনফোর্সমেন্ট | অমীমাংসিত চ্যালেঞ্জকে ট্রু-পজিটিভ ধরা হয়েছে | চ্যালেঞ্জ ও ব্লক আলাদা | ডিটেকশন মেট্রিক এবং এনফোর্সমেন্ট আউটকাম পেপারের টেবিলে আলাদা কলামে দেখানো। |
| **Comment 6** | কন্ট্রোলড বেসলাইন তুলনা | আনুমানিক সংখ্যা দিয়ে তুলনা করা | টেস্টবেড রেডি | একই ট্রাফিকে Laravel Throttling বনাম Redis-Only বনাম HySA-MCP তুলনা টেবিল তৈরি। |
| **Comment 7** | এন্ড-টু-এন্ড ল্যাটেন্সি ও লোড | ল্যাটেন্সি শুধু ৮ms রেডিস বলা হয়েছে | এআই কল ২-৪ সেকেন্ড নেয় | কনকারেন্ট রিকোয়েস্টে p50/p95/p99 ল্যাটেন্সি ও থ্রুপুট বেঞ্চমার্ক যুক্ত করা। |
| **Comment 8** | NAT-অ্যাওয়ার ম্যানিপুলেশন | ৩টি সফল একাউন্ট দিয়ে আইপি ব্লকিং বাইপাস সম্ভব | কোডে ৩টি একাউন্টের শর্ত আছে | অ্যাটাকার পয়জনিং রিস্কের বিশ্লেষণ ও টিটিএল সেফগার্ডের ব্যাখ্যা যোগ করা। |

---

## ৩. পরবর্তী ডকুমেন্টস গাইড
- **`01_REVIEWER_COMMENTS_POINT_BY_POINT_ACTION_PLAN.md`**: রিভিউয়ারের ৮টি পয়েন্টের প্রতিটির বিস্তারিত সমাধান এবং ধাপে ধাপে কী করতে হবে তার গাইডলাইন।
- **`02_MANUSCRIPT_REVISION_AND_REBUTTAL_GUIDE.md`**: পেপারের ম্যানুস্ক্রিপ্ট ও রিবুটাল লেটার (Rebuttal Letter) প্রস্তুত করার সুস্পষ্ট নির্দেশনা।
