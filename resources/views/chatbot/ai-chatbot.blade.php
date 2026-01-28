<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Chat Assistant</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .chat-container { height: calc(100vh - 200px); }
        .message-content { white-space: pre-wrap; word-wrap: break-word; }
        .message-content code { background-color: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-family: monospace; }
        .message-content pre { background-color: #1f2937; color: #f9fafb; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; margin: 0.5rem 0; }
        .message-content pre code { background-color: transparent; padding: 0; color: inherit; }
        .message-content table { width: 100%; border-collapse: collapse; margin: 0.5rem 0; font-size: 0.875rem; }
        .message-content table th, .message-content table td { border: 1px solid #e5e7eb; padding: 0.5rem; text-align: left; }
        .message-content table th { background-color: #f3f4f6; }
        .message-user { justify-content: flex-end; }
        .message-ai { justify-content: flex-start; }
        .timestamp { font-size: 0.75rem; color: #9ca3af; margin-top: 0.25rem; display: block; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen py-8">
        <div class="max-w-4xl mx-auto px-4">
            <!-- Chat Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-t-lg px-6 py-4 shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2h14a2 2 0 012 2v8a2 2 0 01-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-white font-semibold text-lg">AI Assistant</h1>
                            <p class="text-blue-100 text-sm">Powered by MiniMax</p>
                        </div>
                    </div>
                    <button onclick="clearChat()" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Clear Chat</button>
                </div>
            </div>

            <!-- Chat Messages -->
            <div id="chatMessages" class="chat-container bg-gray-50 overflow-y-auto p-6 space-y-4 border-x border-gray-200" style="height: calc(100vh - 200px);">
            </div>

            <!-- Loading Indicator -->
            <div id="typingIndicator" class="typing-indicator hidden px-6 py-4 bg-gray-50 border-x border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 18-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <div class="bg-white rounded-lg px-4 py-3 shadow-sm">
                        <p class="text-gray-600">AI is thinking...</p>
                    </div>
                </div>
            </div>

            <!-- Chat Input -->
            <div class="bg-white border border-gray-200 rounded-b-lg px-4 py-4 shadow-lg">
                <form id="chatForm" class="flex items-end space-x-3">
                    <div class="flex-1 relative">
                        <textarea id="userInput" rows="1" placeholder="Type your message... (Ask in Bangla or English)" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none" required></textarea>
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center space-x-2">
                        <span>Send</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18 9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </button>
                </form>
                <p class="text-gray-400 text-xs mt-2 text-center">Press Enter to send, Shift+Enter for new line</p>
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

        window.onload = () => {
            loadHistory();
            if (messages.length === 0) {
                addMessage(
                    'assistant',
                    'Hello! 👋 আমি Pragati Life AI Assistant। English বা বাংলা যেকোনো ভাষায় প্রশ্ন করতে পারেন।'
                );
            } else {
                renderMessages();
            }
        };

        function loadHistory() {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved) {
                try { messages = JSON.parse(saved); } catch {}
            }
        }

        function saveHistory() {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(messages));
        }

        function renderMessages() {
            chatMessagesEl.innerHTML = '';
            messages.forEach(msg => {
                const div = document.createElement('div');
                div.className = 'flex items-start space-x-3 ' + (msg.role === 'user' ? 'flex-row-reverse space-x-reverse' : '');
                
                const time = new Date(msg.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                
                if (msg.role === 'user') {
                    div.innerHTML = `
                        <div class="w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 9-2z"></path>
                            </svg>
                        </div>
                        <div class="bg-blue-600 rounded-lg px-4 py-3 shadow-sm max-w-md">
                            <p class="text-white message-content" style="white-space: pre-wrap;">${escapeHtml(msg.content)}</p>
                            <span class="timestamp" style="text-align: right;">${time}</span>
                        </div>
                    `;
                } else {
                    div.innerHTML = `
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1 1-.75-3M3 13h18M5 17h14a2 2 0 01-2 2V6a2 2 0 012 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="bg-white rounded-lg px-4 py-3 shadow-sm max-w-md">
                            <p class="text-gray-800 message-content" style="white-space: pre-wrap;">${formatMessage(msg.content)}</p>
                            <span class="timestamp">${time}</span>
                        </div>
                    `;
                }
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
            typingIndicator.style.display = 'flex';

            try {
                const res = await fetch('/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content')
                    },
                    body: JSON.stringify({ message: text })
                });

                const data = await res.json();
                typingIndicator.classList.add('d-none');
                typingIndicator.style.display = 'none';

                addMessage(
                    'assistant',
                    data.reply || 'দুঃখিত, আমি বুঝতে পারিনি। আবার চেষ্টা করুন।'
                );
            } catch (err) {
                console.error(err);
                typingIndicator.classList.add('d-none');
                typingIndicator.style.display = 'none';
                addMessage(
                    'assistant',
                    '⚠️ সার্ভারে সংযোগ করা যাচ্ছে না। কিছুক্ষণ পর আবার চেষ্টা করুন।'
                );
            }
        }

        chatForm.addEventListener('submit', e => {
            e.preventDefault();
            handleUserSubmit(userInput.value);
        });

        userInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleUserSubmit(userInput.value);
            }
        });

        userInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 150) + 'px';
        });

        window.clearChat = () => {
            if (confirm('Delete conversation history?')) {
                messages = [];
                localStorage.removeItem(STORAGE_KEY);
                addMessage(
                    'assistant',
                    'History cleared ✅ নতুন করে শুরু করুন।'
                );
            }
        };

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatMessage(text) {
            let formatted = escapeHtml(text);
            formatted = formatted.replace(/\n/g, '<br>');
            formatted = formatted.replace(/```(\w+)?\n([\s\S]*?)```/g, '<pre class="bg-gray-800 text-white p-3 rounded mt-2 mb-2 overflow-x-auto"><code>$2</code></pre>');
            formatted = formatted.replace(/`([^`]+)`/g, '<code class="bg-gray-100 px-1 rounded text-red-600">$1</code>');
            formatted = formatted.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
            return formatted;
        }
    </script>
</body>
</html>