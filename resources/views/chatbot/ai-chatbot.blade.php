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
      /* New Color Palette: Indigo & Deep Slate */
      --bg-gradient: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
      --accent-color: #6366f1;
      --accent-hover: #4f46e5;
      --glass-bg: rgba(255, 255, 255, 0.05);
      --glass-border: rgba(255, 255, 255, 0.12);
      --text-main: #f8fafc;
    }

    body, html {
      height: 100vh;
      margin: 0;
      padding: 0;
      overflow: hidden; /* Prevents page-level scrolling */
      font-family: 'Inter', sans-serif;
      background: var(--bg-gradient);
      color: var(--text-main);
    }

    /* Layout Constraints */
    .main-wrapper {
      height: 100vh;
      display: flex;
      flex-direction: column;
      padding: 1rem 2rem;
    }

    .content-row {
      flex: 1;
      min-height: 0; /* Important for flex child scrolling */
      margin-bottom: 1rem;
    }

    .glass-card {
      background: var(--glass-bg);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid var(--glass-border);
      border-radius: 24px;
      box-shadow: 0 12px 40px 0 rgba(0, 0, 0, 0.3);
    }

    .glass-button {
      background: rgba(255, 255, 255, 0.08);
      backdrop-filter: blur(8px);
      border: 1px solid var(--glass-border);
      color: white;
      border-radius: 12px;
      padding: 8px 20px;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-weight: 500;
      font-size: 0.9rem;
    }

    .glass-button:hover {
      background: var(--accent-color);
      color: white;
      border-color: var(--accent-color);
      transform: translateY(-1px);
    }

    /* Chat Container Styles */
    .chat-container {
      height: 100%;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      background: rgba(15, 23, 42, 0.6);
    }

    .chat-header {
      padding: 16px 24px;
      background: rgba(255, 255, 255, 0.03);
      border-bottom: 1px solid var(--glass-border);
    }

    .chat-messages {
      flex-grow: 1;
      overflow-y: auto;
      padding: 24px;
      scrollbar-width: thin;
      scrollbar-color: var(--accent-color) transparent;
    }

    .message {
      margin-bottom: 20px;
      max-width: 85%;
      padding: 14px 18px;
      border-radius: 18px;
      font-size: 0.95rem;
      line-height: 1.5;
    }

    .message-user {
      align-self: flex-end;
      background: var(--accent-color);
      color: white;
      border-bottom-right-radius: 4px;
      margin-left: auto;
      box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
    }

    .message-ai {
      align-self: flex-start;
      background: rgba(255, 255, 255, 0.07);
      border: 1px solid var(--glass-border);
      color: #e2e8f0;
      border-bottom-left-radius: 4px;
    }

    .timestamp {
      font-size: 0.7rem;
      opacity: 0.5;
      display: block;
      margin-top: 6px;
    }

    .chat-input-area {
      padding: 20px;
      background: rgba(0, 0, 0, 0.2);
      border-top: 1px solid var(--glass-border);
    }

    .form-control-custom {
      background: rgba(255, 255, 255, 0.05) !important;
      border: 1px solid var(--glass-border) !important;
      color: white !important;
      border-radius: 15px !important;
      padding: 12px 20px !important;
    }

    .form-control-custom:focus {
      border-color: var(--accent-color) !important;
      box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
    }

    /* Action Cards */
    .sidebar-actions {
      display: flex;
      flex-direction: column;
      gap: 12px;
      overflow-y: auto;
      height: 100%;
    }

    .action-card {
      padding: 16px;
      transition: all 0.3s ease;
      cursor: pointer;
      border: 1px solid var(--glass-border);
      background: rgba(255, 255, 255, 0.03);
    }

    .action-card:hover {
      background: rgba(255, 255, 255, 0.08);
      border-color: var(--accent-color);
      transform: translateX(5px);
    }

    .icon-box {
      width: 45px;
      height: 45px;
      background: rgba(99, 102, 241, 0.15);
      color: var(--accent-color);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .online-badge {
      font-size: 0.75rem;
      color: #4ade80;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .online-dot {
      width: 8px;
      height: 8px;
      background: #4ade80;
      border-radius: 50%;
      box-shadow: 0 0 10px #4ade80;
    }

    .btn-send {
      background: var(--accent-color);
      border: none;
      width: 50px;
      height: 50px;
      border-radius: 12px;
      transition: 0.3s;
    }

    .btn-send:hover {
      background: var(--accent-hover);
      transform: scale(1.05);
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--accent-color); }
  </style>
</head>
<body>

  <div class="main-wrapper">
    <!-- Header -->
    <header class="glass-card mb-3 p-3 d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center gap-3 ps-2">
        <img class="bg-white p-1" src="{{ asset('vms/logo/pragatiLogo.png') }}" style="height: 50px; width: 110px; border-radius:8px;" alt="Logo">
        <div>
          <h5 class="mb-0 fw-bold" style="letter-spacing: -0.5px;">Pragati Life</h5>
          <small class="text-uppercase opacity-50" style="font-size: 0.6rem; letter-spacing: 2px;">Insurance AI</small>
        </div>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('dashboard') }}" class="glass-button"><i class="fas fa-th-large"></i> Dashboard</a>
      </div>
    </header>

    <!-- Main Content Grid -->
    <div class="row g-4 content-row">
      <!-- Left Column: Actions -->
      <div class="col-lg-4 d-none d-lg-block">
        <div class="sidebar-actions">
          <div class="glass-card action-card d-flex align-items-center gap-3" onclick="triggerAction('Calculate Premium')">
            <div class="icon-box"><i class="fas fa-calculator fs-5"></i></div>
            <div>
              <h6 class="mb-0 fw-semibold">Premium Calculator</h6>
              <small class="opacity-50">Instant policy quotes</small>
            </div>
          </div>

          <div class="glass-card action-card d-flex align-items-center gap-3" onclick="triggerAction('Check Policy Status')">
            <div class="icon-box"><i class="fas fa-shield-alt fs-5"></i></div>
            <div>
              <h6 class="mb-0 fw-semibold">Policy Status</h6>
              <small class="opacity-50">Track your coverage</small>
            </div>
          </div>

          <div class="glass-card action-card d-flex align-items-center gap-3" onclick="triggerAction('How to file a claim')">
            <div class="icon-box"><i class="fas fa-file-signature fs-5"></i></div>
            <div>
              <h6 class="mb-0 fw-semibold">File a Claim</h6>
              <small class="opacity-50">Quick claim processing</small>
            </div>
          </div>

          <div class="glass-card action-card d-flex align-items-center gap-3" onclick="triggerAction('Show Frequently Asked Questions')">
            <div class="icon-box"><i class="fas fa-comment-dots fs-5"></i></div>
            <div>
              <h6 class="mb-0 fw-semibold">Support FAQ</h6>
              <small class="opacity-50">Common questions</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Chatbot -->
      <div class="col-lg-8 h-100">
        <div class="glass-card chat-container">
          <!-- Chat Header -->
          <div class="chat-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
              <div class="position-relative">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; background: var(--accent-color);">
                  <i class="fas fa-robot text-white"></i>
                </div>
              </div>
              <div>
                <h6 class="mb-0 fw-bold">AI Assistant</h6>
                <div class="online-badge">
                  <span class="online-dot"></span> <small>Active</small>
                </div>
              </div>
            </div>
            <div class="d-flex gap-3">
              <button class="btn btn-link p-0 text-white opacity-50" onclick="clearChat()" title="Clear History"><i class="fas fa-rotate-left"></i></button>
              <button class="btn btn-link p-0 text-white opacity-50"><i class="fas fa-ellipsis-v"></i></button>
            </div>
          </div>

          <!-- Messages Area -->
          <div class="chat-messages d-flex flex-column" id="chatMessages">
            <!-- Messages load here -->
          </div>

          <!-- Typing Indicator -->
          <div id="typingIndicator" class="px-4 py-2 d-none">
            <div class="message message-ai" style="width: fit-content;">
              <div class="typing-indicator">
                <small class="opacity-50">Thinking...</small>
              </div>
            </div>
          </div>

          <!-- Input Area -->
          <div class="chat-input-area">
            <form id="chatForm" class="d-flex gap-2">
              <input type="text" id="userInput" class="form-control form-control-custom" placeholder="Ask about policies, claims, or premiums..." autocomplete="off">
              <button type="submit" class="btn btn-send text-white">
                <i class="fas fa-paper-plane"></i>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    /* Logic remains exactly as provided, only handling UI updates */
    const STORAGE_KEY = 'pragati_chat_history_v3';
    const chatMessagesEl = document.getElementById('chatMessages');
    const chatForm = document.getElementById('chatForm');
    const userInput = document.getElementById('userInput');
    const typingIndicator = document.getElementById('typingIndicator');

    let messages = [];

    window.onload = () => {
      loadHistory();
      if (messages.length === 0) {
        addMessage('assistant', "Hello! I am Pragati Life's AI Assistant. How can I assist you with your insurance needs today?");
      } else {
        renderMessages();
      }
    };

    function loadHistory() {
      const saved = localStorage.getItem(STORAGE_KEY);
      if (saved) {
        try { messages = JSON.parse(saved); } catch (e) { console.error(e); }
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
      messages.push({ role, content, timestamp: new Date().toISOString() });
      renderMessages();
      saveHistory();
    }

    window.triggerAction = (text) => handleUserSubmit(text);

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
        addMessage('assistant', data.reply || "I'm having trouble connecting. Please try again.");
      } catch (error) {
        typingIndicator.classList.add('d-none');
        addMessage('assistant', "Connection error. Please check your internet.");
      }
    }

    chatForm.addEventListener('submit', (e) => {
      e.preventDefault();
      handleUserSubmit(userInput.value);
    });

    window.clearChat = () => {
      if(confirm("Clear conversation history?")) {
        messages = [];
        localStorage.removeItem(STORAGE_KEY);
        addMessage('assistant', "History cleared. How can I help you?");
      }
    };
  </script>
</body>
</html>