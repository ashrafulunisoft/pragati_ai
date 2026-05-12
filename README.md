# AI Insurance Chatbot Platform

An enterprise-grade multilingual AI chatbot platform built with <a href="https://laravel.com">Laravel</a>, <a href="https://laravel.com/docs/octane">Laravel Octane</a>, <a href="https://roadrunner.dev">RoadRunner</a>, <a href="https://reverb.laravel.com">Laravel Reverb</a>, Docker, Redis, MySQL, NGINX, and GitLab CI/CD.

The platform is designed for intelligent insurance automation and customer support. It can understand and respond naturally in multiple languages while handling insurance-related workflows including policy purchase, claim tracking, package details, customer order management, and coverage explanations.

---

# 🚀 Features

* 🤖 AI Chatbot Integration
* 🌍 Multilingual Chat Support
* 🧠 MCP Minimax Model Integration
* 🔄 Support for Multiple AI Models
* 💬 Real-Time Chat Messaging
* 🔔 Real-Time Notifications using Laravel Reverb
* ⚡ High Performance with Laravel Octane + RoadRunner
* 📑 Insurance Policy Management
* 💳 Insurance Purchase Assistance
* 📦 Insurance Package Details
* 🛡️ Coverage Amount Explanation
* 📋 Claim Management
* 👤 Customer Order Tracking
* 📈 Intelligent Insurance Q&A
* 🧾 Customer Support Automation
* 🐳 Fully Dockerized Infrastructure
* 🔐 Secure Authentication & Authorization
* 🚀 GitLab CI/CD Deployment
* 🌐 NGINX Reverse Proxy

---

# 🏗️ Tech Stack

| Layer                 | Technology                       |
| --------------------- | -------------------------------- |
| Backend               | PHP 8.3, Laravel                 |
| AI Layer              | MCP Minimax Model                |
| Alternative AI Models | OpenAI, Claude, Gemini, DeepSeek |
| Realtime              | Laravel Reverb                   |
| Performance           | Laravel Octane + RoadRunner      |
| Queue & Cache         | Redis                            |
| Database              | MySQL 8                          |
| Reverse Proxy         | NGINX                            |
| Containerization      | Docker & Docker Compose          |
| CI/CD                 | GitLab CI/CD                     |
| Database Management   | phpMyAdmin                       |
| Frontend              | React / Next.js / Vue            |

---

# 📦 Core Capabilities

## 🤖 AI Insurance Assistant

The chatbot can:

* Answer insurance-related questions
* Explain policy details
* Describe insurance coverage
* Handle claim-related queries
* Assist with insurance purchases
* Track customer orders
* Provide package comparisons
* Answer premium-related questions
* Guide customers through claim processes
* Support multilingual communication

---

# 🌍 Multilingual Support

Supported capabilities:

* English
* Bangla
* Hindi
* Arabic
* Urdu
* Spanish
* French
* Dynamic language detection

The AI automatically detects and responds in the customer's language.

---

# 🧠 AI Model Architecture

```text id="m8r2q5"
Customer Message
        │
        ▼
Laravel API Gateway
        │
        ▼
AI Service Layer
        │
 ┌──────┼───────────────┐
 │      │               │
 ▼      ▼               ▼
Minimax OpenAI       Gemini
 │
 ▼
Insurance Knowledge Engine
        │
        ▼
Realtime Response via Reverb
```

---

# 📁 Project Structure

```bash id="k3v8x1"
project-root/
│
├── app/
│   ├── AI/
│   ├── Services/
│   ├── Models/
│   ├── Events/
│   ├── Jobs/
│   └── Http/
│
├── bootstrap/
├── config/
├── database/
├── docker/
│   ├── nginx/
│   ├── mysql/
│   ├── redis/
│   └── phpmyadmin/
│
├── resources/
├── routes/
├── storage/
│
├── .gitlab-ci.yml
├── docker-compose.yml
├── Dockerfile
└── README.md
```

---

# ⚙️ Requirements

* PHP 8.3+
* Composer
* Docker
* Docker Compose
* Node.js 20+
* GitLab Account
* VPS Server

---

# 🐳 Docker Services

| Service        | Port |
| -------------- | ---- |
| Laravel App    | 8000 |
| NGINX          | 80   |
| MySQL          | 3306 |
| Redis          | 6379 |
| phpMyAdmin     | 8081 |
| Laravel Reverb | 8080 |

---

# 🐋 Docker Compose Setup

## Start Containers

```bash id="v7n5m2"
docker compose up -d
```

---

## Stop Containers

```bash id="q2x6k8"
docker compose down
```

---

## Rebuild Containers

```bash id="d5p1r4"
docker compose up -d --build
```

---

# 🔧 Local Laravel Setup

## Install Dependencies

```bash id="u6m9z2"
composer install
npm install
```

---

## Copy Environment File

```bash id="w3k8v7"
cp .env.example .env
```

---

## Generate Application Key

```bash id="y4q2x6"
php artisan key:generate
```

---

# 🐬 MySQL Configuration

```env id="f8r1m5"
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=insurance_ai
DB_USERNAME=insurance_user
DB_PASSWORD=strong_password
```

---

# 🔴 Redis Configuration

```env id="a2v9p4"
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

# ⚡ Laravel Octane Setup

## Install Octane

```bash id="n7m2v6"
composer require laravel/octane
```

---

## Install RoadRunner

```bash id="r4x8k1"
composer require spiral/roadrunner-cli spiral/roadrunner-http
```

---

## Install Octane Server

```bash id="t9q5m3"
php artisan octane:install --server=roadrunner
```

---

# ▶️ Run Laravel Octane

```bash id="s8v1p7"
php artisan octane:start --server=roadrunner --host=0.0.0.0 --port=8000
```

---

# 📡 Laravel Reverb Setup

## Install Reverb

```bash id="c7m3q2"
composer require laravel/reverb
```

---

## Configure Reverb

```bash id="b5x1v9"
php artisan reverb:install
```

---

## Run Reverb Server

```bash id="j6p4m8"
php artisan reverb:start
```

---

# 🔔 Broadcasting Configuration

```env id="z4r8m1"
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=insurance-ai
REVERB_APP_KEY=insurance-key
REVERB_APP_SECRET=insurance-secret

REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=https
```

---

# 🤖 MCP Minimax AI Integration

## Supported AI Providers

| Provider    | Status    |
| ----------- | --------- |
| MCP Minimax | Primary   |
| OpenAI      | Supported |
| Claude      | Supported |
| Gemini      | Supported |
| DeepSeek    | Supported |

---

# 🧠 AI Features

* Context-aware conversations
* Insurance knowledge reasoning
* Intelligent package recommendation
* Claim explanation engine
* Customer history understanding
* Multi-turn conversations
* Real-time response streaming

---

# 📑 Insurance Management Modules

## 📦 Insurance Package Module

Features:

* Package listing
* Coverage details
* Premium calculation
* Policy comparison
* Eligibility checking

---

## 🛡️ Coverage Management

The AI can explain:

* Coverage amount
* Deductibles
* Exclusions
* Premium benefits
* Claim limitations
* Policy validity

---

## 📋 Claims Management

Supported operations:

* Claim status checking
* Claim submission guidance
* Required document explanation
* Claim timeline tracking
* Settlement information

---

## 👤 Customer Management

Features:

* Customer profile
* Order tracking
* Purchase history
* Chat history
* AI interaction logs

---

# 🌐 NGINX Reverse Proxy

```nginx id="h8m2v5"
server {
    listen 80;

    server_name your-domain.com;

    client_max_body_size 100M;

    location / {
        proxy_pass http://app:8000;

        proxy_http_version 1.1;

        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }

    location /app {
        proxy_pass http://reverb:8080;

        proxy_http_version 1.1;

        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";

        proxy_set_header Host $host;
    }
}
```

---

# 🚀 GitLab CI/CD

## Deployment Pipeline

```text id="p2v9x7"
Developer Push
        │
        ▼
GitLab CI/CD
        │
        ▼
Docker Build
        │
        ▼
VPS Deployment
        │
        ▼
Docker Compose Restart
        │
        ▼
Production Ready
```

---

# 📁 `.gitlab-ci.yml`

```yaml id="m6q3v1"
stages:
  - deploy

deploy_production:
  stage: deploy

  only:
    - main

  before_script:
    - apt-get update -y
    - apt-get install -y openssh-client

  script:
    - mkdir -p ~/.ssh
    - echo "$SSH_PRIVATE_KEY" > ~/.ssh/id_rsa
    - chmod 600 ~/.ssh/id_rsa

    - ssh -o StrictHostKeyChecking=no root@$SERVER_IP "
        cd /var/www/insurance-ai &&
        git pull origin main &&
        docker compose down &&
        docker compose up -d --build
      "
```

---

# 🧪 Testing

## Run Backend Tests

```bash id="x7m4q1"
php artisan test
```

---

## Run Frontend Tests

```bash id="u9v2k5"
npm run test
```

---

# 🔥 Performance Optimization

* Laravel Octane Workers
* RoadRunner Persistent Workers
* Redis Queue Optimization
* NGINX Reverse Proxy
* Query Optimization
* AI Response Caching
* Lazy Loading
* WebSocket Scaling

---

# 🔐 Security Best Practices

* HTTPS / WSS
* API Rate Limiting
* AI Request Validation
* CSRF Protection
* JWT Authentication
* Encrypted Environment Variables
* Secure AI API Key Storage
* Role-Based Access Control

---

# 👨‍💻 Development Commands

## Run Vite

```bash id="c5r8m2"
npm run dev
```

---

## Run Queue Worker

```bash id="j2v6p9"
php artisan queue:work
```

---

## Run Scheduler

```bash id="n1m8q4"
php artisan schedule:work
```

---

## Run Reverb

```bash id="k7x3v5"
php artisan reverb:start
```

---

## Run Octane

```bash id="s4m9q1"
php artisan octane:start --server=roadrunner
```

---

# 📈 Future Improvements

* Voice AI Assistant
* AI Call Center
* OCR Document Reading
* AI Claim Fraud Detection
* Insurance Recommendation Engine
* AI Agent Dashboard
* Mobile Applications
* Multi-Tenant Support

---

# 📊 Real-Time AI Chat Flow

```text id="v8q2m6"
Customer Message
        │
        ▼
Laravel API
        │
        ▼
AI Processing Layer
        │
        ▼
Insurance Knowledge Engine
        │
        ▼
Realtime Streaming via Reverb
        │
        ▼
Customer Receives AI Response
```

---

# 🤝 Contributing

1. Fork Repository
2. Create Feature Branch
3. Commit Changes
4. Push Branch
5. Open Merge Request

---

# 📜 License

MIT License

---

# 🙌 Credits

Built with:

* Laravel
* Laravel Reverb
* Laravel Octane
* RoadRunner
* MCP Minimax
* Redis
* MySQL
* Docker
* GitLab CI/CD
* NGINX
* AI Language Models
