# 03. Research Codebase Architecture Analysis

**Project:** Pragati AI — Research Paper Codebase (`HySA-MCP`)  
**Paper Title:** *HySA-MCP: A Hybrid AI-Driven Secure Authentication Framework with Model Context Protocol (MCP) Integration*  
**Document Generated:** 2026-11-19  
**Directory:** `/Research/Paper_Feedback_From_Journal/fix_problem_form_journal_feedback_oneByone/gemini/`  

---

## ১. ভূমিকা ও আর্কিটেকচারাল সারসংক্ষেপ (Architecture Overview)

প্রজেক্টের গবেষণা বিষয়বস্তু হলো **HySA-MCP** — একটি তিন-স্তরবিশিষ্ট হাইব্রিড সিকিউরিটি ফ্রেমওয়ার্ক:
1. **Tier 1 (Fast-Path Data Layer):** Redis-ভিত্তিক অ্যাটমিক ফিক্সড-উইন্ডো কাউন্টার ও ব্লকলিস্ট (Lua স্ক্রিপ্টেড, ~১-৮ms ল্যাটেন্সি)।
2. **Tier 2 (Protocol & Analysis Layer):** Model Context Protocol (MCP JSON-RPC 2.0 / Streamable HTTP) অথবা Direct LLM ট্রান্সপোর্ট, যা ৪-স্তরের প্রম্পট ইনজেকশন ডিফেন্স এবং HMAC-SHA256 ছদ্মনামকরণসহ কনটেক্সট এনালাইসিস করে।
3. **Tier 3 (Decision & Enforcement Layer):** F1–F5 ডিসিশন ফিউশন ম্যাট্রিক্স ($S = \max(S_{\text{rule}}, S_{\text{AI}})$), যা $\ge 80$ স্কোরে সরাসরি ব্লক (HTTP 429), $50-79$ স্কোরে বাধ্যতামূলক স্টেপ-আপ চ্যালেঞ্জ (HTTP 428 OTP/Math Captcha), এবং $<50$ স্কোরে মনিটর্ড অ্যাক্সেস নিশ্চিত করে।

---

## ২. রাউট অ্যানালাইসিস (Routes Analysis)

গবেষণা সম্পর্কিত রাউটগুলো নিচের ফাইলগুলোতে সুনির্দিষ্টভাবে বিভক্ত:

| ফাইল | মেথড ও পাথ | মিডলওয়্যার / কন্ট্রোলার | গবেষণাগত তাৎপর্য |
|---|---|---|---|
| **`routes/web.php`** (L:177) | `POST /login` | `mcp.malicious.login` $\to$ `LoginController@login` | মূল অ্যাটাক ইন্টারসেপশন গেটওয়ে। প্রতিটি ইনকামিং লগইন চেষ্টা প্রথমে সিকিউরিটি মিডলওয়্যারে যাচাই হয়। |
| **`routes/api.php`** (L:18) | `POST /mcp` | `McpServerController` | স্প্রিংগার পেপারের অন্যতম প্রধান নোবেল্টি (P3 Fix): অফিসিয়াল MCP সার্ভার এন্ডপয়েন্ট (JSON-RPC 2.0, Bearer token authenticated)। |
| **`routes/web.php`** (L:391) | `GET|POST /chat` | `ChatbotController` | প্রজেক্টের ইন্টেলিজেন্ট ইন্স্যুরেন্স অ্যাসিস্ট্যান্ট, যা LLM ইন্টিগ্রেশন ব্যবহার করে দ্বৈত ভাষায় (বাংলা ও ইংরেজি) গ্রাহকসেবা দেয়। |
| **`routes/ai.php`** (L:17) | `POST /mcp/chat` | `VmsChatServer` | লারাভেল MCP ওয়েব স্ট্রিমিং রাউট। |

---

## ৩. কন্ট্রোলার ও মিডলওয়্যার অ্যানালাইসিস (Controllers & Middleware)

### ক. `app/Http/Middleware/McpMaliciousLoginMiddleware.php`
- **কাজ:** ইনকামিং লগইন ট্রাফিকের সিকিউরিটি গেটওয়ে।
- **লজিক ফ্লো:**
  1. `SecurityInputSanitizer` দিয়ে ইনপুট ফিল্টারিং (ম্যালফর্মড ইমেইল হলে এআই কল না করে সরাসরি কন্ট্রোলারের ভ্যালিডেশনে ৪২২-এর জন্য ছেড়ে দেওয়া)।
  2. `SecurityStore` থেকে পূর্বে ব্লকড আইপি বা ইমেইল থাকলে সাথে সাথে রিজেক্ট (এআই কোটা বাঁচানো ও DoS রোধ)।
  3. অ্যাটেম্পট থ্রেশহোল্ড চেক: $\text{IP} \ge 5$ অথবা $\text{Email} \ge 3$ হলে `McpSecurityService::analyzeLogin()` সক্রিয় করা।
  4. সিদ্ধান্ত এনফোর্সমেন্ট: 
     - **MALICIOUS / Score $\ge 80$:** এক ঘণ্টার জন্য আইপি বা ইমেইল ব্লক (HTTP 403/429)।
     - **SUSPICIOUS (Score 50–79):** HTTP 428 Precondition Required রেসপন্সসহ OTP/Captcha চ্যালেঞ্জ জারি করা।
     - **Degraded Mode (AI Down):** ৩য় স্ট্রাইকে ১৫ মিনিটের এমার্জেন্সি থ্রটল ব্লক জারি করা।

### খ. `app/Http/Controllers/Auth/LoginController.php`
- **কাজ:** ক্রেডেনশিয়াল প্রমাণীকরণ ও রেডিস কাউন্টার লাইফসাইকেল ম্যানেজমেন্ট (রিভিউয়ারের Comment 4 এর সমাধান)।
- **লজিক:**
  - অথেনটিকেশন ব্যর্থ হলে (`!Auth::attempt`): Lua স্ক্রিপ্ট দিয়ে ফিক্সড-উইন্ডোতে কাউন্টার বৃদ্ধি (`attack:login:ip`, `attack:login:email`)।
  - অথেনটিকেশন সফল হলে: ইমেইল কাউন্টার রিসেট করা এবং ওই আইপির সফল অ্যাকাউন্টের তালিকায় যুক্ত করা (`success:emails:ip:$ip`)।

### গ. `app/Http/Controllers/Mcp/McpServerController.php`
- **কাজ:** আসল MCP JSON-RPC 2.0 প্রোটোকল সার্ভার।
- **মেথড সাপোর্ট:** `initialize` (হ্যান্ডশেক ও সেশন আইডি তৈরি), `notifications/initialized`, `tools/list` (টুল বিজ্ঞাপন), `tools/call` (টুল চালানো), `ping`।

### ঘ. `app/Http/Controllers/Chatbot/ChatbotController.php`
- **কাজ:** দ্বৈত ভাষার লাইফ ইন্স্যুরেন্স চ্যাটবট অ্যাসিস্ট্যান্ট।
- **সংযোগ:** OpenAI-compatible ফরম্যাটে LLM কল (`/v1/chat/completions`) করে পলিসি ক্রয়, ক্লেইম সাবমিশন ও সাধারণ প্রশ্নোত্তর হ্যান্ডেল করে।

---

## ৪. সার্ভিস লেয়ার ও MCP কোর অ্যানালাইসিস (Services & MCP Core)

### ১. `app/Services/McpSecurityService.php` (The Decision Brain)
- **রুল-বেসড স্কোরিং ফ্লোর:**
  - IP প্রচেষ্টা: $\ge 10 \to 50$ পয়েন্ট, $\ge 5 \to 30$ পয়েন্ট, $\ge 3 \to 15$ পয়েন্ট।
  - Email প্রচেষ্টা: $\ge 5 \to 40$ পয়েন্ট, $\ge 3 \to 25$ পয়েন্ট।
- **ডিসিশন ফিউশন ম্যাট্রিক্স (F1–F5):**
  - **F1:** $\text{FinalScore} \ge 80 \implies \text{MALICIOUS}$ (ডিটারমিনিস্টিক হার্ড গার্ডরেইল)।
  - **F2:** $\text{AI} = \text{MALICIOUS}$ এবং $\text{FinalScore} \ge 50 \implies \text{MALICIOUS}$ (এআই-এর এসকেলেশন ক্ষমতা)।
  - **F3:** $\text{AI} = \text{SAFE}$ ($\text{Conf} \ge 0.8$) এবং $\text{Score } 50-79 \implies \text{SUSPICIOUS}$ (সীমাবদ্ধ ছাড়; কখনোই সরাসরি Allow নয়)।
  - **F4:** $\text{FinalScore} \ge 50 \implies \text{SUSPICIOUS}$ (মিডিয়াম ব্যান্ড স্টেপ-আপ)।
  - **F5:** $\text{Otherwise} \implies \text{SAFE}$।
- **ড্রাইভার সুইচ:** `direct` (সরাসরি LLM) বনাম `mcp` (JSON-RPC MCP প্রোটোকল ক্লায়েন্ট)।

### ২. `app/Services/SecurityStore.php` (Atomic Redis & Fallback)
- **Lua স্ক্রিপ্টিং:** `INCR_WITH_TTL_LUA` দিয়ে একই অ্যাটমিক অপারেশনে কাউন্টার বৃদ্ধি ও উইন্ডো আর্ম করা।
- **টিটিএল সেলফ-হিলিং:** রেস কন্ডিশনে কোনো চাবি টিটিএল ছাড়া থাকলে স্বয়ংক্রিয়ভাবে টিটিএল সেট করা।
- **ফেইলওভার:** রেডিস অফলাইন হলে লারাভেল ক্যাশে স্বয়ংক্রিয়ভাবে ফলব্যাক।

### ৩. `app/Services/ChallengeService.php` (HTTP 428 State Machine)
- OTP এবং Math Captcha চ্যালেঞ্জ তৈরি ও একক-ব্যবহারযোগ্য (single-use) টোকেন ভ্যালিডেশন।
- চ্যালেঞ্জ ফ্লাডিং রোধে প্রতি আইপিতে সর্বোচ্চ ১০টি চ্যালেঞ্জের রেট-লিমিট।

### ৪. `app/Services/PrivacyService.php` (HMAC Pseudonymization)
- `email` $\to$ `email_id:<16-char HMAC-SHA256>`
- `ip` $\to$ `ip_net:<prefix>~<8-char HMAC>`
- কোনো PII (ব্যক্তিগত তথ্য) বাইরের এআই সার্ভারে যায় না।

### ৫. `app/Services/SecurityInputSanitizer.php` (Prompt Injection Defense)
- কন্ট্রোল ক্যারেক্টার ও ডিলিমিটার বাদ দেওয়া, কঠোর সিনট্যাক্স ভ্যালিডেশন।

### ৬. `app/Mcp/SecurityPrompt.php`
- সিস্টেম প্রম্পটে `<context>...</context>` দিয়ে ডেটাকে আইসোলেট করা এবং মডেলকে ইনস্ট্রাকশন অগ্রাহ্য করার কঠোর নিয়ম দেওয়া।

### ৭. `app/Console/Commands/SecurityEvaluateCommand.php`
- পেপারের গবেষণার মূল হারনেস। ৭টি বাস্তবসম্মত আক্রমণ সিনারিও (Typos, Brute Force, Credential Stuffing, Password Spraying, Distributed Stuffing, Low-and-Slow, Shared NAT) সিমুলেট করে সঠিক কনফিউশন ম্যাট্রিক্স বের করে।

---

## ৫. কনফিগারেশন অ্যানালাইসিস (`config/services.php`)

`config/services.php` এর `minimax` অ্যারেটি সম্পূর্ণ গবেষণার প্যারামিটার নিয়ন্ত্রণ করে:
- **এআই কানেকশন:** `api_key`, `host`, `model`, `provider`।
- **ডিগ্রেডেড মোড:** `timeout (10s)`, `retries (1)`, `degraded_threshold (3 strikes)`, `degraded_block_ttl (900s)`।
- **থ্রেশহোল্ড:** `threshold_ip_analyze (5)`, `threshold_email_analyze (3)`, `window_seconds (900s)`, `block_ttl (3600s)`।
- **রিস্ক ব্যান্ডস:** `risk_band_malicious (80)`, `risk_band_suspicious (50)`, `risk_band_captcha (30)`।
- **NAT ও চ্যালেঞ্জ:** `shared_ip_success_threshold (3)`, `challenge_enabled (true)`, `challenge_ttl (300s)`।
- **MCP প্রোটোকল:** `driver ('direct'|'mcp')`, `mcp_protocol_version ('2025-06-18')`।

---

## ৬. টেস্ট ফাইলসমূহ অ্যানালাইসিস (Unit Tests Suite)

প্রজেক্টের `tests/Unit/` ফোল্ডারে মোট ১০টি গবেষণা সংক্রান্ত টেস্ট স্যুট রয়েছে, যার **১০৬টি টেস্টের সবকটিই সফলভাবে পাস (106/106 Passed)**:

| টেস্ট স্যুট | টেস্ট সংখ্যা | যাচাইকৃত গবেষণার বিষয়বস্তু |
|---|:---:|---|
| **`McpAtomicRedisTest`** | ৫ | Lua স্ক্রিপ্ট অ্যাটোমিসিটি, ফিক্সড উইন্ডো টিটিএল সেলফ-হিলিং, ক্যাশ ফলব্যাক। |
| **`McpChallengeEnforcementTest`** | ১০ | HTTP 428 স্টেপ-আপ চ্যালেঞ্জ, OTP/Captcha সঠিক/ভুল ইনপুট, টোকেন একক ব্যবহার। |
| **`McpConfigurableThresholdsTest`** | ৬ | রানটাইমে `.env` থেকে থ্রেশহোল্ড, ব্যান্ড ও ব্লকিং সময় পরিবর্তন সক্ষমতা। |
| **`McpDecisionFusionTest`** | ৯ | F1–F5 ডিসিশন ফিউশন ম্যাট্রিক্স, এআই ভ্যালিডেশন, রুল ফ্লোর সুরক্ষা। |
| **`McpFailoverPolicyTest`** | ৬ | এআই সার্ভার ডাউন থাকলে টিয়ার্ড ফেইলওভার ও এমার্জেন্সি থ্রটলিং। |
| **`McpNatMitigationTest`** | ৫ | NAT শেয়ার্ড আইপিতে অন্য ইউজারকে ক্ষতিগ্রস্ত না করে শুধু অ্যাটাকিং অ্যাকাউন্ট ব্লক করা। |
| **`McpPrivacyPseudonymizationTest`** | ৭ | HMAC-SHA256 হ্যাশিং, বহির্গামী ট্রাফিকে কোনো কাঁচা ইমেইল/আইপি না থাকা। |
| **`McpPromptInjectionTest`** | ১০ | ক্ষতিকর ইনপুট ফিল্টারিং, প্রম্পট ইনজেকশন দিয়ে হাই-রিস্ক স্কোর ফাঁকি দেওয়া অসম্ভব প্রমাণ করা। |
| **`McpProtocolTest`** | ১২ | আসল MCP JSON-RPC 2.0 হ্যান্ডশেক, টুল রিকোয়েস্ট/রেসপন্স ও এরর কোড। |
| **`McpSecurityLoginTest`** | ৩৬ | এন্ড-টু-এন্ড ইন্টিগ্রেশন ফ্লো ও বিভিন্ন আক্রমণ সিনারিওতে সঠিক ডিসিশন ম্যাপিং। |

---

## ৭. পরিবেশ ভেরিয়েবল অ্যানালাইসিস (`.env`)

প্রজেক্টের `.env` ফাইলে গবেষণার সমস্ত কনফিগারেশন ডায়নামিকভাবে সংযুক্ত রয়েছে:

```env
# 1. AI Engine (Google Gemini Free Tier - OpenAI Compatible)
MCP_PROVIDER=gemini
MINIMAX_API_KEY=your_gemini_api_key_here
MINIMAX_API_HOST=https://generativelanguage.googleapis.com/v1beta/openai
MCP_MODEL=gemini-3.1-flash-lite

# 2. Protocol & Driver Switching
MCP_DRIVER=direct                            # direct (baseline) vs mcp (novelty)
MCP_SERVER_URL=http://127.0.0.1:8000/api/mcp
MCP_SERVER_TOKEN=a6edda31191bc4b0cf8a5b0bfa413ece2de4c8b08d66644fb9bea2eb56724fe0
MCP_PROTOCOL_VERSION=2025-06-18
MCP_TIMEOUT=10
MCP_RETRIES=1

# 3. Privacy Layer
MCP_PRIVACY_PSEUDONYMIZE=true
MCP_PRIVACY_SALT=f8e2ffca7ae3005152822e9c23b2d5258885dbd5f6ddfa0105032628c3efd9b9

# 4. Thresholds & Temporal Windows
LOGIN_ATTEMPT_THRESHOLD_IP=5
LOGIN_ATTEMPT_THRESHOLD_EMAIL=3
ATTEMPT_WINDOW_SECONDS=900                    # 15 minutes fixed window
BLOCK_DURATION_SECONDS=3600                   # 1 hour hard block

# 5. Risk Scoring Bands
MCP_RISK_BAND_MALICIOUS=80                    # Block threshold
MCP_RISK_BAND_SUSPICIOUS=50                   # Step-up challenge (OTP) threshold
MCP_RISK_BAND_CAPTCHA=30                      # Step-up challenge (Captcha) threshold

# 6. NAT & Step-Up Challenge Configuration
MCP_SHARED_IP_SUCCESS_THRESHOLD=3             # Accounts required to mark IP as NAT
MCP_CHALLENGE_ENABLED=true
MCP_CHALLENGE_TTL=300                         # Challenge validity: 5 minutes
MCP_CHALLENGE_MAX_ATTEMPTS=3
MCP_CHALLENGE_MAX_PER_IP=10
MCP_OTP_DELIVERY=log
```

---

## ৮. গবেষণাগত মূল্যায়ন ও উপসংহার (Verdict)

- **কোডবেজের পরিপক্কতা:** কোডবেজটি একটি সাধারণ প্রোটোটাইপ থেকে অনেক দূর এগিয়ে একটি **উচ্চমানের পিয়ার-রিভিউড জার্নাল স্ট্যান্ডার্ডে** উন্নীত হয়েছে।
- **কনসিস্টেন্সি:** রাউট, মিডলওয়্যার, সার্ভিস, কনফিগ এবং টেস্ট ফাইলের মধ্যে সম্পূর্ণ সামঞ্জস্য রয়েছে।
- **জার্নাল পেপারের সাথে অমিল:** কোডবেজে ইতিমধ্যে অনেক উন্নত ফিক্স (যেমন F1–F5 ফিউশন, রিয়েল MCP, ৪ স্তর বিশিষ্ট ইনজেকশন ডিফেন্স) কার্যকর থাকলেও, পূর্বে জমা দেওয়া LaTeX পেপারে (`HySA-MCP-1.pdf`) এই উন্নত অ্যালগরিদম ও লাইভ রেজাল্ট প্রতিফলিত হয়নি।
- **পরবর্তী প্রধান কাজ:** এই সুসংগঠিত আর্কিটেকচার ও টেস্ট রেজাল্টগুলোকে সরাসরি পেপারের অ্যালগরিদম ১ ও ২ এবং ফলাফল সেকশনে ট্রান্সফার করে রিভিশন ড্রাফট সম্পন্ন করা।
