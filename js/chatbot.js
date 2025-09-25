/**
 * AI-Solutions Chatbot Widget
 * Interactive chatbot that fetches responses from database
 */

class ChatbotWidget {
    constructor() {
        this.isOpen = false;
        this.messages = [];
        this.isTyping = false;
        this.init();
    }

    init() {
        this.createWidget();
        this.bindEvents();
        this.addWelcomeMessage();
    }

    createWidget() {
        // Create chatbot container
        const chatbotHTML = `
            <div id="chatbot-widget" class="chatbot-widget">
                <!-- Chatbot Header -->
                <div class="chatbot-header">
                    <div class="chatbot-title">
                        <div class="chatbot-avatar">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="chatbot-info">
                            <h4>AI Assistant</h4>
                            <span class="status">Online</span>
                        </div>
                    </div>
                    <div class="chatbot-controls">
                        <button class="chatbot-minimize" id="chatbotMinimize">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button class="chatbot-close" id="chatbotClose">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Chatbot Messages -->
                <div class="chatbot-messages" id="chatbotMessages">
                    <!-- Messages will be inserted here -->
                </div>

                <!-- Typing Indicator -->
                <div class="chatbot-typing" id="chatbotTyping" style="display: none;">
                    <div class="typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>

                <!-- Chatbot Input -->
                <div class="chatbot-input">
                    <input type="text" id="chatbotInput" placeholder="Type your message...">
                    <button id="chatbotSend">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>

            <!-- Chatbot Toggle Button -->
            <div class="chatbot-toggle" id="chatbotToggle">
                <div class="chatbot-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="chatbot-notification" id="chatbotNotification" style="display: none;">
                    <i class="fas fa-circle"></i>
                </div>
            </div>
        `;

        // Add to page
        document.body.insertAdjacentHTML('beforeend', chatbotHTML);

        // Add styles
        this.addStyles();
    }

    addStyles() {
        const styles = `
            <style>
                .chatbot-widget {
                    position: fixed;
                    bottom: 8rem;
                    right: 2rem;
                    width: 35rem;
                    height: 50rem;
                    background: white;
                    border-radius: 1rem;
                    box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.1);
                    display: flex;
                    flex-direction: column;
                    z-index: 1000;
                    transform: translateY(100%) scale(0.8);
                    opacity: 0;
                    transition: all 0.3s ease;
                    border: 1px solid #e0e0e0;
                }

                .chatbot-widget.open {
                    transform: translateY(0) scale(1);
                    opacity: 1;
                }

                .chatbot-toggle {
                    position: fixed;
                    bottom: 2rem;
                    right: 2rem;
                    width: 6rem;
                    height: 6rem;
                    background: linear-gradient(135deg, #FFD700, #FFA500);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    box-shadow: 0 0.5rem 1.5rem rgba(255, 215, 0, 0.3);
                    transition: all 0.3s ease;
                    z-index: 1001;
                }

                .chatbot-toggle:hover {
                    transform: scale(1.1);
                    box-shadow: 0 0.8rem 2rem rgba(255, 215, 0, 0.4);
                }

                .chatbot-icon {
                    font-size: 2.4rem;
                    color: #2F2F2F;
                }

                .chatbot-notification {
                    position: absolute;
                    top: -0.5rem;
                    right: -0.5rem;
                    width: 1.5rem;
                    height: 1.5rem;
                    background: #DC143C;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    animation: pulse 2s infinite;
                }

                .chatbot-notification i {
                    font-size: 0.8rem;
                    color: white;
                }

                @keyframes pulse {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.2); }
                    100% { transform: scale(1); }
                }

                .chatbot-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 1.5rem;
                    border-bottom: 1px solid #e0e0e0;
                    background: linear-gradient(135deg, #FFD700, #FFA500);
                    border-radius: 1rem 1rem 0 0;
                }

                .chatbot-title {
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                }

                .chatbot-avatar {
                    width: 4rem;
                    height: 4rem;
                    background: rgba(255, 255, 255, 0.2);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.8rem;
                    color: #2F2F2F;
                }

                .chatbot-info h4 {
                    margin: 0;
                    color: #2F2F2F;
                    font-size: 1.6rem;
                    font-weight: 600;
                }

                .chatbot-info .status {
                    font-size: 1.2rem;
                    color: #2F2F2F;
                    opacity: 0.8;
                }

                .chatbot-controls {
                    display: flex;
                    gap: 0.5rem;
                }

                .chatbot-controls button {
                    width: 3rem;
                    height: 3rem;
                    border: none;
                    background: rgba(255, 255, 255, 0.2);
                    border-radius: 50%;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: #2F2F2F;
                    transition: all 0.3s ease;
                }

                .chatbot-controls button:hover {
                    background: rgba(255, 255, 255, 0.3);
                    transform: scale(1.1);
                }

                .chatbot-messages {
                    flex: 1;
                    padding: 1.5rem;
                    overflow-y: auto;
                    display: flex;
                    flex-direction: column;
                    gap: 1rem;
                }

                .chatbot-message {
                    display: flex;
                    gap: 1rem;
                    animation: fadeInUp 0.3s ease;
                }

                .chatbot-message.user {
                    flex-direction: row-reverse;
                }

                .chatbot-message-avatar {
                    width: 3rem;
                    height: 3rem;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.4rem;
                    flex-shrink: 0;
                }

                .chatbot-message.bot .chatbot-message-avatar {
                    background: linear-gradient(135deg, #FFD700, #FFA500);
                    color: #2F2F2F;
                }

                .chatbot-message.user .chatbot-message-avatar {
                    background: #e0e0e0;
                    color: #2F2F2F;
                }

                .chatbot-message-content {
                    max-width: 80%;
                    padding: 1rem 1.5rem;
                    border-radius: 1.5rem;
                    font-size: 1.4rem;
                    line-height: 1.4;
                }

                .chatbot-message.bot .chatbot-message-content {
                    background: #f5f5f5;
                    color: #2F2F2F;
                    border-bottom-left-radius: 0.5rem;
                }

                .chatbot-message.user .chatbot-message-content {
                    background: linear-gradient(135deg, #FFD700, #FFA500);
                    color: #2F2F2F;
                    border-bottom-right-radius: 0.5rem;
                }

                .chatbot-typing {
                    padding: 0 1.5rem 1rem;
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                }

                .typing-dots {
                    display: flex;
                    gap: 0.3rem;
                    align-items: center;
                    padding: 1rem 1.5rem;
                    background: #f5f5f5;
                    border-radius: 1.5rem;
                    border-bottom-left-radius: 0.5rem;
                }

                .typing-dots span {
                    width: 0.8rem;
                    height: 0.8rem;
                    background: #999;
                    border-radius: 50%;
                    animation: typing 1.4s infinite ease-in-out;
                }

                .typing-dots span:nth-child(1) { animation-delay: -0.32s; }
                .typing-dots span:nth-child(2) { animation-delay: -0.16s; }

                @keyframes typing {
                    0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
                    40% { transform: scale(1); opacity: 1; }
                }

                .chatbot-input {
                    display: flex;
                    padding: 1.5rem;
                    border-top: 1px solid #e0e0e0;
                    gap: 1rem;
                }

                .chatbot-input input {
                    flex: 1;
                    padding: 1rem 1.5rem;
                    border: 1px solid #e0e0e0;
                    border-radius: 2rem;
                    font-size: 1.4rem;
                    outline: none;
                    transition: all 0.3s ease;
                }

                .chatbot-input input:focus {
                    border-color: #FFD700;
                    box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
                }

                .chatbot-input button {
                    width: 4rem;
                    height: 4rem;
                    border: none;
                    background: linear-gradient(135deg, #FFD700, #FFA500);
                    border-radius: 50%;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: #2F2F2F;
                    font-size: 1.6rem;
                    transition: all 0.3s ease;
                }

                .chatbot-input button:hover {
                    transform: scale(1.1);
                    box-shadow: 0 0.5rem 1rem rgba(255, 215, 0, 0.3);
                }

                .chatbot-input button:disabled {
                    opacity: 0.5;
                    cursor: not-allowed;
                    transform: none;
                }

                @keyframes fadeInUp {
                    from {
                        opacity: 0;
                        transform: translateY(2rem);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                /* Mobile Responsive */
                @media (max-width: 768px) {
                    .chatbot-widget {
                        width: calc(100vw - 2rem);
                        height: calc(100vh - 12rem);
                        bottom: 1rem;
                        right: 1rem;
                        left: 1rem;
                    }

                    .chatbot-toggle {
                        bottom: 1rem;
                        right: 1rem;
                    }
                }

                /* Scrollbar Styling */
                .chatbot-messages::-webkit-scrollbar {
                    width: 0.5rem;
                }

                .chatbot-messages::-webkit-scrollbar-track {
                    background: #f1f1f1;
                    border-radius: 0.25rem;
                }

                .chatbot-messages::-webkit-scrollbar-thumb {
                    background: #c1c1c1;
                    border-radius: 0.25rem;
                }

                .chatbot-messages::-webkit-scrollbar-thumb:hover {
                    background: #a8a8a8;
                }
            </style>
        `;

        document.head.insertAdjacentHTML('beforeend', styles);
    }

    bindEvents() {
        const toggle = document.getElementById('chatbotToggle');
        const close = document.getElementById('chatbotClose');
        const minimize = document.getElementById('chatbotMinimize');
        const send = document.getElementById('chatbotSend');
        const input = document.getElementById('chatbotInput');
        const widget = document.getElementById('chatbot-widget');

        toggle.addEventListener('click', () => this.toggle());
        close.addEventListener('click', () => this.close());
        minimize.addEventListener('click', () => this.minimize());
        send.addEventListener('click', () => this.sendMessage());
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.sendMessage();
            }
        });

        // Auto-open on page load after delay
        setTimeout(() => {
            this.showNotification();
        }, 5000);
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        const widget = document.getElementById('chatbot-widget');
        widget.classList.add('open');
        this.isOpen = true;
        this.hideNotification();
        
        // Focus input
        setTimeout(() => {
            document.getElementById('chatbotInput').focus();
        }, 300);
    }

    close() {
        const widget = document.getElementById('chatbot-widget');
        widget.classList.remove('open');
        this.isOpen = false;
    }

    minimize() {
        this.close();
    }

    showNotification() {
        if (!this.isOpen) {
            document.getElementById('chatbotNotification').style.display = 'block';
        }
    }

    hideNotification() {
        document.getElementById('chatbotNotification').style.display = 'none';
    }

    addWelcomeMessage() {
        this.addMessage('bot', "Hello! I'm your AI assistant. How can I help you today? You can ask me about our services, contact information, pricing, or any other questions about AI-Solutions.");
    }

    addMessage(sender, message) {
        const messagesContainer = document.getElementById('chatbotMessages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `chatbot-message ${sender}`;
        
        const avatar = document.createElement('div');
        avatar.className = 'chatbot-message-avatar';
        avatar.innerHTML = sender === 'bot' ? '<i class="fas fa-robot"></i>' : '<i class="fas fa-user"></i>';
        
        const content = document.createElement('div');
        content.className = 'chatbot-message-content';
        content.textContent = message;
        
        messageDiv.appendChild(avatar);
        messageDiv.appendChild(content);
        messagesContainer.appendChild(messageDiv);
        
        // Scroll to bottom
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    showTyping() {
        const typing = document.getElementById('chatbotTyping');
        typing.style.display = 'flex';
        document.getElementById('chatbotMessages').scrollTop = document.getElementById('chatbotMessages').scrollHeight;
    }

    hideTyping() {
        document.getElementById('chatbotTyping').style.display = 'none';
    }

    async sendMessage() {
        const input = document.getElementById('chatbotInput');
        const sendBtn = document.getElementById('chatbotSend');
        const message = input.value.trim();
        
        if (!message || this.isTyping) return;
        
        // Add user message
        this.addMessage('user', message);
        input.value = '';
        
        // Disable input
        input.disabled = true;
        sendBtn.disabled = true;
        this.isTyping = true;
        
        // Show typing indicator
        this.showTyping();
        
        try {
            const response = await fetch('chatbot.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message: message })
            });
            
            const data = await response.json();
            
            // Hide typing indicator
            this.hideTyping();
            
            if (data.success) {
                // Simulate typing delay
                setTimeout(() => {
                    this.addMessage('bot', data.response);
                }, 500);
            } else {
                this.addMessage('bot', data.message || 'Sorry, I encountered an error. Please try again.');
            }
            
        } catch (error) {
            this.hideTyping();
            this.addMessage('bot', 'Sorry, I\'m having trouble connecting. Please try again later.');
            console.error('Chatbot error:', error);
        } finally {
            // Re-enable input
            input.disabled = false;
            sendBtn.disabled = false;
            this.isTyping = false;
            input.focus();
        }
    }
}

// Initialize chatbot when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ChatbotWidget();
});
