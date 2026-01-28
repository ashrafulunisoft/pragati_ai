<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pragati Life AI Assistant</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <style>
    :root {
      --bg-gradient: linear-gradient(135deg, #4ab59a 0%, #0d5540 100%);
      --glass-bg: rgba(255, 255, 255, 0.12);
      --glass-border: rgba(255, 255, 255, 0.2);
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg-gradient);
      min-height: 100vh;
      color: white;
      margin: 0;
      padding-bottom: 60px;
    }

    .glass-card {
      background: var(--glass-bg);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid var(--glass-border);
      border-radius: 28px;
      box-shadow: 0 12px 40px 0 rgba(0, 0, 0, 0.25);
    }

    .glass-button {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(8px);
      border: 1px solid var(--glass-border);
      color: white;
      border-radius: 50px;
      padding: 10px 24px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-weight: 500;
    }

    .glass-button:hover {
      background: rgba(255, 255, 255, 0.2);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }

    .chat-container {
      height: 800px; 
      display: flex;
      flex-direction: column;
      overflow: hidden;
      width: 100%;
    }

    .chat-header {
      padding: 20px;
      background: rgba(255, 255, 255, 0.95);
      border-bottom: 1px solid rgba(0,0,0,0.05);
      color: #1f2937;
    }

    .chat-messages {
      flex-grow: 1;
      overflow-y: auto;
      padding: 25px;
      background: rgba(255, 255, 255, 0.03);
    }

    .message {
      margin-bottom: 20px;
      max-width: 80%;
      padding: 16px 20px;
      border-radius: 20px;
      font-size: 0.95rem;
      line-height: 1.6;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .message-user {
      align-self: flex-end;
      background: #059669;
      color: white;
      border-bottom-right-radius: 4px;
      margin-left: auto;
    }

    .message-ai {
      align-self: flex-start;
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255,255,255,0.2);
      color: white;
      border-bottom-left-radius: 4px;
    }

    .timestamp {
      font-size: 0.7rem;
      opacity: 0.6;
      display: block;
      margin-top: 6px;
    }

    .chat-input-area {
      padding: 20px;
      background: rgba(0, 0, 0, 0.15);
      border-top: 1px solid var(--glass-border);
    }

    .form-control-custom {
      background: rgba(255, 255, 255, 0.1) !important;
      border: 1px solid var(--glass-border) !important;
      color: white !important;
      border-radius: 35px !important;
      padding: 14px 25px !important;
      font-size: 1rem;
    }

    .form-control-custom:focus {
      background: rgba(255, 255, 255, 0.15) !important;
      box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.3);
    }

    .action-card {
      transition: all 0.3s ease;
      cursor: pointer;
      border: 1px solid rgba(255,255,255,0.1);
    }

    .action-card:hover {
      background: rgba(255, 255, 255, 0.2);
      transform: scale(1.02) translateX(5px);
      border-color: rgba(255,255,255,0.3);
    }

    .online-badge {
      font-size: 0.7rem;
      background: rgba(16, 185, 129, 0.1);
      color: #10b981;
      padding: 2px 8px;
      border-radius: 10px;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }

    .online-dot {
      width: 6px;
      height: 6px;
      background: #10b981;
      border-radius: 50%;
      box-shadow: 0 0 8px #10b981;
    }

    .typing-indicator span {
      height: 7px;
      width: 7px;
      background: white;
      display: inline-block;
      border-radius: 50%;
      opacity: 0.4;
      animation: typing 1s infinite;
      margin: 0 2px;
    }

    @keyframes typing {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-6px); }
    }
  </style>
</head>
<body>

  <div class="container py-5">
    <!-- Header -->
    <header class="glass-card mb-5 p-3 d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center gap-3 ps-2">
        <div class="bg-white rounded-3 p-1 shadow-sm" style="width: 44px; height: 44px; display: grid; place-items: center;">
          <i class="fas fa-leaf text-success fs-4"></i>
        </div>
        <div>
          <h5 class="mb-0 fw-bold">Pragati Life</h5>
          <small class="text-uppercase opacity-75" style="font-size: 0.65rem; letter-spacing: 1.5px; font-weight: 600;">Insurance PLC</small>
        </div>
      </div>
      <div class="d-flex gap-3 pe-2">
        <a href="#" class="glass-button"><i class="fas fa-globe"></i> English</a>
        <a href="#" class="glass-button d-none d-md-flex"><i class="fas fa-user-circle"></i> Login</a>
      </div>
    </header>

    <!-- Main Content Grid -->
    <div class="row g-5 align-items-start">
      <!-- Left Column: Info & Actions -->
      <div class="col-lg-5">
        <div class="glass-card p-4 mb-4" style="border-left: 5px solid #10b981;">
          <h2 class="fw-bold mb-3 lh-base">Secure Your Future Today</h2>
          <p class="opacity-80 mb-4 fs-5">Empowering thousands of families in Bangladesh with trusted insurance solutions. Start a conversation to find your perfect plan.</p>
          <div class="d-flex gap-3">
            <span class="badge rounded-pill bg-white bg-opacity-10 border border-white border-opacity-20 py-2 px-3"><i class="fas fa-check-circle me-1 text-success"></i> Fast Claims</span>
            <span class="badge rounded-pill bg-white bg-opacity-10 border border-white border-opacity-20 py-2 px-3"><i class="fas fa-shield-alt me-1 text-success"></i> Trusted</span>
          </div>
        </div>

        <div class="d-flex flex-column gap-3">
          <div class="glass-card p-4 action-card d-flex align-items-center gap-4" onclick="triggerAction('Calculate Premium')">
            <div class="bg-success bg-opacity-20 rounded-4 p-3 text-emerald-300">
              <i class="fas fa-calculator fs-4"></i>
            </div>
            <div>
              <h6 class="mb-1 fw-bold fs-5">Premium Calculator</h6>
              <small class="opacity-60">Check expected premiums for policies.</small>
            </div>
          </div>

          <div class="glass-card p-4 action-card d-flex align-items-center gap-4" onclick="triggerAction('Check Policy Status')">
            <div class="bg-success bg-opacity-20 rounded-4 p-3 text-emerald-300">
              <i class="fas fa-search fs-4"></i>
            </div>
            <div>
              <h6 class="mb-1 fw-bold fs-5">Policy Status</h6>
              <small class="opacity-60">View your active insurance details.</small>
            </div>
          </div>

          <div class="glass-card p-4 action-card d-flex align-items-center gap-4" onclick="triggerAction('How to file a claim')">
            <div class="bg-success bg-opacity-20 rounded-4 p-3 text-emerald-300">
              <i class="fas fa-file-invoice-dollar fs-4"></i>
            </div>
            <div>
              <h6 class="mb-1 fw-bold fs-5">File a Claim</h6>
              <small class="opacity-60">Submit and track insurance claims.</small>
            </div>
          </div>

          <div class="glass-card p-4 action-card d-flex align-items-center gap-4" onclick="triggerAction('Show Frequently Asked Questions')">
            <div class="bg-success bg-opacity-20 rounded-4 p-3 text-emerald-300">
              <i class="fas fa-question-circle fs-4"></i>
            </div>
            <div>
              <h6 class="mb-1 fw-bold fs-5">FAQ</h6>
              <small class="opacity-60">Frequently Asked Questions.</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Chatbot -->
      <div class="col-lg-7">
        <div class="glass-card chat-container shadow-lg">
          <!-- Chat Header -->
          <div class="chat-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
              <div class="position-relative">
                <div class="rounded-circle bg-success d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                  <i class="fas fa-robot text-white fs-4"></i>
                </div>
              </div>
              <div>
                <h6 class="mb-0 fw-bold text-dark">Pragati AI Assistant</h6>
                <div class="online-badge">
                  <span class="online-dot"></span> Online Now
                </div>
              </div>
            </div>
            <div class="d-flex gap-4 text-secondary opacity-75">
              <button class="btn btn-link p-0 text-dark" onclick="clearChat()" title="Clear History"><i class="fas fa-trash-alt"></i></button>
              <button class="btn btn-link p-0 text-dark"><i class="fas fa-phone-alt"></i></button>
              <button class="btn btn-link p-0 text-dark"><i class="fas fa-ellipsis-v"></i></button>
            </div>
          </div>

          <!-- Messages Area -->
          <div class="chat-messages d-flex flex-column" id="chatMessages">
          </div>

          <!-- Typing Indicator -->
          <div id="typingIndicator" class="px-4 py-2 d-none">
            <div class="message message-ai">
              <div class="typing-indicator">
                <span></span> <span></span> <span></span>
              </div>
            </div>
          </div>

          <!-- Input Area -->
          <div class="chat-input-area">
            <form id="chatForm" class="d-flex gap-3 align-items-center">
              <input type="text" id="userInput" class="form-control form-control-custom" placeholder="Type your message here..." autocomplete="off">
              <button type="submit" class="btn btn-success rounded-circle d-flex align-items-center justify-content-center shadow-lg" style="width: 54px; height: 54px; flex-shrink: 0;">
                <i class="fas fa-paper-plane fs-5"></i>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    const STORAGE_KEY = 'pragati_chat_history_v3';
    const chatMessagesEl = document.getElementById('chatMessages');
    const chatForm = document.getElementById('chatForm');
    const userInput = document.getElementById('userInput');
    const typingIndicator = document.getElementById('typingIndicator');

    let messages = [];

    // Initialize
    window.onload = () => {
      loadHistory();
      if (messages.length === 0) {
        addMessage('assistant', "Hello! I am Pragati Life's AI Assistant. I can help you with premium calculations, policy details, or claim processes. How can I help you today?");
      } else {
        renderMessages();
      }
    };

    function loadHistory() {
      const saved = localStorage.getItem(STORAGE_KEY);
      if (saved) {
        try {
          messages = JSON.parse(saved);
        } catch (e) {
          console.error("Failed to load history", e);
        }
      }
    }

    function saveHistory() {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(messages));
    }

    function renderMessages() {
      chatMessagesEl.innerHTML = '';
      messages.forEach(msg => {
        const div = document.createElement('div');
        div.className = `message ${msg.role === 'user' ? 'message-user' : 'message-ai'}`;
        div.innerHTML = `
          <div style="white-space: pre-wrap;">${msg.content}</div>
          <span class="timestamp">${new Date(msg.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
        `;
        chatMessagesEl.appendChild(div);
      });
      chatMessagesEl.scrollTop = chatMessagesEl.scrollHeight;
    }

    function addMessage(role, content) {
      messages.push({
        role,
        content,
        timestamp: new Date().toISOString()
      });
      renderMessages();
      saveHistory();
    }

    window.triggerAction = (text) => {
      handleUserSubmit(text);
    };

    async function handleUserSubmit(text) {
      if (!text.trim()) return;
      
      addMessage('user', text);
      userInput.value = '';
      
      typingIndicator.classList.remove('d-none');
      chatMessagesEl.scrollTop = chatMessagesEl.scrollHeight;

      try {
        const res = await fetch('/chat', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({ message: text })
        });

        const data = await res.json();
        typingIndicator.classList.add('d-none');
        
        if (data.reply) {
          addMessage('assistant', data.reply);
        } else {
          addMessage('assistant', "I'm having a little trouble connecting to my servers right now. Please try again in a moment.");
        }
      } catch (error) {
        console.error("Error:", error);
        typingIndicator.classList.add('d-none');
        addMessage('assistant', "I'm having a little trouble connecting to my servers right now. Please try again in a moment.");
      }
    }

    chatForm.addEventListener('submit', (e) => {
      e.preventDefault();
      handleUserSubmit(userInput.value);
    });

    window.clearChat = () => {
      if(confirm("Delete conversation history?")) {
        messages = [];
        localStorage.removeItem(STORAGE_KEY);
        addMessage('assistant', "History cleared. How can I help you starting fresh?");
      }
    };
  </script>
</body>
</html>
