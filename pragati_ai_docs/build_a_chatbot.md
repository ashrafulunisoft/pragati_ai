Got it 👍
You already have **Laravel 12 running in Docker**, and you want **a simple, fast (1-day doable) way** to:

* Run a **local AI model** (Microsoft Phi / small model)
* Connect it to **Laravel via MCP-style tool calling**
* Build a **smart chatbot** that:

  * Chats in **Bangla + English**
  * Answers **FAQ / policy questions**
  * Can **CRUD Packages, Orders, Claims**
  * Feels like a human assistant

Below is the **easiest + practical architecture** (no over-engineering).

---

## 🔥 Reality Check (Important)

There is **no official “Laravel MCP package”** yet.
MCP (Model Context Protocol) is **just a standard**.

👉 **Best practical approach**:

* Run **local LLM server** (Ollama / LM Studio / llama.cpp)
* Expose it via **HTTP API**
* Laravel acts as:

  * **Chat Orchestrator**
  * **Tool executor (CRUD)**

This **IS MCP in practice**.

---

## 🧠 Recommended Stack (Simple & Works)

| Part           | Tool                           |
| -------------- | ------------------------------ |
| Local AI Model | **Ollama**                     |
| Model          | **phi-3:mini** (Microsoft)     |
| Language       | Bangla + English               |
| AI ↔ Laravel   | HTTP (JSON)                    |
| Chat Logic     | Laravel Service + Tool Mapping |
| Time to build  | ✅ 1 day                        |

---

## 🧱 High Level Architecture (ASCII)

```
User (Web Chat UI)
        |
        v
Laravel Controller
        |
        v
Chat Orchestrator Service
        |
        +----------------------+
        |                      |
        v                      v
Local AI Model (Ollama)    Laravel Tools
(phi-3-mini)              (CRUD APIs)
        |
        v
AI Response
        |
        v
User
```

---

## 🐳 STEP 1: Run Microsoft Phi Model in Docker

### 1️⃣ Add Ollama to docker-compose

```yaml
services:
  ollama:
    image: ollama/ollama
    container_name: ollama
    ports:
      - "11434:11434"
    volumes:
      - ollama:/root/.ollama
    restart: unless-stopped

volumes:
  ollama:
```

```bash
docker compose up -d
```

---

### 2️⃣ Pull Microsoft Phi model

```bash
docker exec -it ollama ollama pull phi3:mini
```

Test:

```bash
curl http://localhost:11434/api/generate \
  -d '{"model":"phi3:mini","prompt":"Hello"}'
```

---

## ⚙️ STEP 2: Laravel AI Service (MCP Core)

### 1️⃣ Create AI Service

```bash
php artisan make:service AIChatService
```

---

### 2️⃣ AIChatService (Brain)

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AIChatService
{
    public function chat(string $message, array $context = [])
    {
        $systemPrompt = $this->systemPrompt();

        $response = Http::post('http://ollama:11434/api/generate', [
            'model' => 'phi3:mini',
            'prompt' => $systemPrompt . "\nUser: " . $message,
            'stream' => false
        ]);

        return $response->json()['response'] ?? 'Sorry, I could not understand.';
    }

    private function systemPrompt(): string
    {
        return <<<PROMPT
You are a customer support AI assistant for an Insurance system.
You can talk in Bangla and English.

You have tools:
- get_user_info
- list_packages
- create_order
- submit_claim
- faq_answer

If user intent is CRUD, respond ONLY in JSON:

{
  "tool": "tool_name",
  "data": { }
}

Otherwise reply normally.
PROMPT;
    }
}
```

---

## 🛠 STEP 3: Tool Router (MCP Tools)

### 1️⃣ Create Tool Controller

```bash
php artisan make:controller ChatbotController
```

---

### 2️⃣ ChatbotController

```php
public function chat(Request $request, AIChatService $ai)
{
    $reply = $ai->chat($request->message);

    if ($this->isToolCall($reply)) {
        return $this->handleTool(json_decode($reply, true));
    }

    return response()->json(['reply' => $reply]);
}
```

---

### 3️⃣ Tool Handler

```php
private function handleTool(array $call)
{
    return match ($call['tool']) {
        'list_packages' => Package::all(),
        'create_order'  => Order::create($call['data']),
        'submit_claim'  => Claim::create($call['data']),
        default => ['error' => 'Unknown tool'],
    };
}
```

---

## 🗣 STEP 4: Bangla + English Prompt Examples

User says:

```
আমার জন্য available insurance package দেখাও
```

AI outputs:

```json
{
  "tool": "list_packages",
  "data": {}
}
```

---

User says:

```
Order this package for me
```

```json
{
  "tool": "create_order",
  "data": {
    "package_id": 3,
    "user_id": 12
  }
}
```

---

## 📚 STEP 5: FAQ & Policy

### Store FAQ in DB or JSON

```php
FAQ::where('question', 'LIKE', "%claim%")->first();
```

Tell AI:

```
If question is policy or FAQ, answer from FAQ data.
```

---

## 🧩 STEP 6: Simple Chat UI (Blade + JS)

```html
<input id="msg" />
<button onclick="send()">Send</button>

<script>
function send(){
  fetch('/chat', {
    method:'POST',
    headers:{
      'Content-Type':'application/json',
      'X-CSRF-TOKEN':'{{ csrf_token() }}'
    },
    body: JSON.stringify({ message: msg.value })
  })
  .then(r=>r.json())
  .then(d=>console.log(d));
}
</script>
```

---

## ✅ What You Achieved

✔ Local AI (No OpenAI cost)
✔ Microsoft Phi model
✔ MCP-style tool calling
✔ Bangla + English
✔ CRUD via chatbot
✔ Laravel native
✔ Docker ready

---

## 🚀 Want Next?

I can give you:

* 🔐 Auth-aware chatbot (user id auto detect)
* 🧠 Memory (chat history)
* 📊 Admin panel to watch chats
* 🗺 Real-time chat (Laravel Reverb)

Just tell me 👍
