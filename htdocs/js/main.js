/**
 * AI-Solution Website JavaScript
 * Author: AI-Solution Team
 * Version: 1.0
 */

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Set current year in footer
    document.getElementById('year').textContent = new Date().getFullYear();

    // Header scroll effect
    const header = document.getElementById('header');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 100) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // Mobile menu toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.nav-menu');

    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent click from propagating
            navMenu.classList.toggle('active');
            menuToggle.classList.toggle('active');

            // Toggle menu icon
            if (menuToggle.classList.contains('active')) {
                menuToggle.innerHTML = '<i class="fas fa-times"></i>';
            } else {
                menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
            }
        });
    }

    // Close mobile menu when clicking outside or on a menu item
    document.addEventListener('click', function(event) {
        if (navMenu && navMenu.classList.contains('active')) {
            // If clicking on a menu item or outside the nav
            if (event.target.closest('.nav-menu a') || !event.target.closest('nav')) {
                navMenu.classList.remove('active');
                if (menuToggle) {
                    menuToggle.classList.remove('active');
                    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                }
            }
        }
    });

    // Set active menu item based on current page
    const setActiveMenuItem = () => {
        const currentPage = window.location.pathname.split('/').pop();
        const menuItems = document.querySelectorAll('.nav-menu a');

        menuItems.forEach(item => {
            // Remove active class from all items first
            item.classList.remove('active');

            // Get the href value and extract the page name
            const itemPage = item.getAttribute('href');

            // Check if the current page matches this menu item
            if (currentPage === '' || currentPage === '/' || currentPage === 'index.html') {
                // We're on the home page
                if (itemPage === 'index.html') {
                    item.classList.add('active');
                }
            } else if (currentPage === itemPage) {
                item.classList.add('active');
            } else {
                // Handle case when PHP and HTML extensions might differ
                const currentPageName = currentPage.split('.')[0];
                const itemPageName = itemPage.split('.')[0];

                if (currentPageName === itemPageName) {
                    item.classList.add('active');
                }
            }
        });
    };

    // Call the function on page load
    setActiveMenuItem();

    // Stats counter animation
    const stats = document.querySelectorAll('.stat-number');
    if (stats.length > 0) {
        const statsSection = document.getElementById('stats');

        // Function to check if element is in viewport
        function isInViewport(element) {
            const rect = element.getBoundingClientRect();
            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        }

        // Function to animate counter
        function animateCounter(counter, target) {
            let count = 0;
            const speed = 2000 / target; // Adjust animation speed based on target value

            const updateCount = () => {
                if (count < target) {
                    count++;
                    counter.textContent = count;
                    setTimeout(updateCount, speed);
                } else {
                    counter.textContent = target;
                }
            };

            updateCount();
        }

        // Check if stats section is in viewport and start animation
        let animated = false;
        window.addEventListener('scroll', function() {
            if (statsSection && isInViewport(statsSection) && !animated) {
                stats.forEach(stat => {
                    const target = parseInt(stat.getAttribute('data-count'));
                    animateCounter(stat, target);
                });
                animated = true;
            }
        });

        // Initial check on page load
        if (statsSection && isInViewport(statsSection)) {
            stats.forEach(stat => {
                const target = parseInt(stat.getAttribute('data-count'));
                animateCounter(stat, target);
            });
            animated = true;
        }
    }

    // Smooth scrolling for internal links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                window.scrollTo({
                    top: target.offsetTop - 80, // Adjust for header height
                    behavior: 'smooth'
                });
            }
        });
    });

    // Form validation
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            let valid = true;
            const name = document.getElementById('name');
            const email = document.getElementById('email');
            const message = document.getElementById('message');

            // Simple validation
            if (name && name.value.trim() === '') {
                showError(name, 'Name is required');
                valid = false;
            } else if (name) {
                removeError(name);
            }

            if (email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (email.value.trim() === '') {
                    showError(email, 'Email is required');
                    valid = false;
                } else if (!emailRegex.test(email.value.trim())) {
                    showError(email, 'Please enter a valid email address');
                    valid = false;
                } else {
                    removeError(email);
                }
            }

            if (message && message.value.trim() === '') {
                showError(message, 'Message is required');
                valid = false;
            } else if (message) {
                removeError(message);
            }

            if (!valid) {
                e.preventDefault();
            }
        });

        // Function to show error message
        function showError(input, message) {
            const formGroup = input.parentElement;
            const errorMessage = formGroup.querySelector('.error-message') || document.createElement('div');

            errorMessage.className = 'error-message';
            errorMessage.textContent = message;
            errorMessage.style.color = 'var(--danger-color)';
            errorMessage.style.fontSize = '1.4rem';
            errorMessage.style.marginTop = '0.5rem';

            if (!formGroup.querySelector('.error-message')) {
                formGroup.appendChild(errorMessage);
            }

            input.style.borderColor = 'var(--danger-color)';
        }

        // Function to remove error message
        function removeError(input) {
            const formGroup = input.parentElement;
            const errorMessage = formGroup.querySelector('.error-message');

            if (errorMessage) {
                formGroup.removeChild(errorMessage);
            }

            input.style.borderColor = 'var(--gray-light)';
        }
    }

    // Chatbot functionality
    const chatbotToggle = document.querySelector('.chatbot-toggle');
    const chatbotBox = document.querySelector('.chatbot-box');
    const chatbotClose = document.querySelector('.chatbot-close');
    const chatbotMessages = document.querySelector('.chatbot-messages');
    const chatbotInputField = document.getElementById('chatbot-input-field');
    const chatbotSubmitBtn = document.getElementById('chatbot-submit-btn');

    // Track if the chatbot has been opened before
    let chatbotOpened = false;

    // Toggle chatbot visibility
    if (chatbotToggle && chatbotBox) {
        chatbotToggle.addEventListener('click', function() {
            chatbotBox.classList.add('active');
            chatbotToggle.style.display = 'none';

            // Add welcome message if it's the first time opening
            if (!chatbotOpened) {
                setTimeout(() => {
                    addMessage("Is there anything specific you'd like to know about our AI solutions?", false);
                }, 1000);
                chatbotOpened = true;
            }

            // Scroll to bottom of messages
            chatbotMessages.scrollTop = chatbotMessages.scrollHeight;

            // Focus on input field
            setTimeout(() => {
                chatbotInputField.focus();
            }, 300);
        });
    }

    // Close chatbot
    if (chatbotClose && chatbotBox && chatbotToggle) {
        chatbotClose.addEventListener('click', function() {
            chatbotBox.classList.remove('active');
            chatbotToggle.style.display = 'flex';
        });
    }

    // Handle user message submission
    if (chatbotInputField && chatbotSubmitBtn && chatbotMessages) {
        // Function to add message to chat
        function addMessage(message, isUser = false) {
            const messageDiv = document.createElement('div');
            messageDiv.classList.add('chatbot-message');
            messageDiv.classList.add(isUser ? 'user' : 'bot');

            const messageContent = document.createElement('div');
            messageContent.classList.add('message-content');

            // Process message for links and formatting
            const processedMessage = processMessage(message);
            messageContent.innerHTML = processedMessage;

            messageDiv.appendChild(messageContent);
            chatbotMessages.appendChild(messageDiv);

            // Add animation class
            messageDiv.classList.add('fadeIn');

            // Scroll to bottom of messages
            chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
        }

        // Process message to detect links and add formatting
        function processMessage(message) {
            // Convert URLs to clickable links
            const urlRegex = /(https?:\/\/[^\s]+)/g;
            message = message.replace(urlRegex, '<a href="$1" target="_blank">$1</a>');

            // Bold important terms
            const terms = ['AI Virtual Assistants', 'Workflow Automation', 'Predictive Analytics'];
            terms.forEach(term => {
                const regex = new RegExp(term, 'g');
                message = message.replace(regex, `<strong>${term}</strong>`);
            });

            return message;
        }

        // Function to add typing indicator
        function showTypingIndicator() {
            const typingDiv = document.createElement('div');
            typingDiv.classList.add('chatbot-message', 'bot', 'chatbot-typing');
            typingDiv.setAttribute('id', 'typing-indicator');

            for (let i = 0; i < 3; i++) {
                const dot = document.createElement('div');
                dot.classList.add('typing-dot');
                typingDiv.appendChild(dot);
            }

            chatbotMessages.appendChild(typingDiv);
            chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
        }

        // Function to remove typing indicator
        function removeTypingIndicator() {
            const typingIndicator = document.getElementById('typing-indicator');
            if (typingIndicator) {
                typingIndicator.remove();
            }
        }

        // Handle sending message
        function sendMessage() {
            const message = chatbotInputField.value.trim();

            if (message !== '') {
                // Add user message
                addMessage(message, true);

                // Clear input field
                chatbotInputField.value = '';

                // Show typing indicator
                showTypingIndicator();

                // Determine response timing based on message complexity
                const delay = Math.min(1000 + message.length * 20, 3000);

                // Simulate bot response after delay
                setTimeout(() => {
                    // Remove typing indicator
                    removeTypingIndicator();

                    // Add bot response based on user message
                    const botResponse = getBotResponse(message);
                    addMessage(botResponse);

                    // Add follow-up message for certain responses
                    if (botResponse.includes('our team at info@ai-solution.com')) {
                        setTimeout(() => {
                            showTypingIndicator();

                            setTimeout(() => {
                                removeTypingIndicator();
                                addMessage("Would you like me to tell you more about our services?");
                            }, 1500);
                        }, 1000);
                    }
                }, delay);
            }
        }

        // Enhanced bot responses
        function getBotResponse(message) {
            message = message.toLowerCase();

            // Greeting patterns
            if (message.includes('hello') || message.includes('hi') || message.includes('hey')) {
                return 'Hello! How can I assist you today with our AI solutions?';
            }
            // Service inquiries
            else if (message.includes('services') || message.includes('what do you offer') || message.includes('solutions')) {
                return 'We offer three main services: <strong>AI Virtual Assistants</strong> to help employees navigate complex systems, <strong>Workflow Automation</strong> to streamline business processes, and <strong>Predictive Analytics</strong> to help forecast trends and make data-driven decisions. Which one would you like to know more about?';
            }
            // Specific service questions
            else if (message.includes('virtual assistant') || message.includes('assistant')) {
                return 'Our AI Virtual Assistants help employees navigate complex systems and find information quickly. They can answer questions, provide guidance, and automate routine tasks to improve productivity.';
            }
            else if (message.includes('workflow') || message.includes('automation')) {
                return 'Our Workflow Automation solutions use AI to streamline business processes, reduce manual work, and eliminate repetitive tasks. This leads to increased efficiency, reduced errors, and cost savings.';
            }
            else if (message.includes('analytics') || message.includes('predictive')) {
                return 'Our Predictive Analytics solutions help businesses anticipate trends, understand customer behavior, and make data-driven decisions. We use advanced machine learning algorithms to identify patterns and provide actionable insights.';
            }
            // Contact information
            else if (message.includes('contact') || message.includes('talk to human') || message.includes('speak') || message.includes('call') || message.includes('email')) {
                return 'You can reach our team at <a href="mailto:info@ai-solution.com">info@ai-solution.com</a> or call us at +44 191 123 4567. Our office is located at 1 Software Way, Sunderland, UK.';
            }
            // Pricing information
            else if (message.includes('price') || message.includes('cost') || message.includes('pricing') || message.includes('how much')) {
                return 'Our pricing varies based on your specific business needs and the complexity of the solution. We offer customized packages starting from £5,000 for basic implementations. Please contact our sales team for a detailed quote tailored to your requirements.';
            }
            // Implementation questions
            else if (message.includes('implement') || message.includes('integration') || message.includes('setup')) {
                return 'Implementation typically takes 4-8 weeks depending on the complexity of your systems. Our team will work closely with you throughout the process, from initial assessment to testing and deployment.';
            }
            // Benefits and ROI
            else if (message.includes('benefit') || message.includes('roi') || message.includes('return') || message.includes('value')) {
                return 'Our clients typically see ROI within 6-12 months. Benefits include increased productivity (25-40% on average), reduced operational costs, improved decision-making, and enhanced employee experience.';
            }
            // Case studies or examples
            else if (message.includes('example') || message.includes('case') || message.includes('study') || message.includes('client')) {
                return 'We\'ve helped numerous organizations across industries. For example, TechVision Ltd saw a 65% reduction in information retrieval time, and FinServe Group improved their market forecasting accuracy by 40%. You can find more case studies in our Portfolio section.';
            }
            // About company
            else if (message.includes('about') || message.includes('company') || message.includes('who are you')) {
                return 'AI-Solution is a leading provider of AI-powered software solutions. We focus on enhancing digital employee experiences and driving business innovation through cutting-edge AI technology. Learn more on our About Us page.';
            }
            // Gratitude
            else if (message.includes('thank') || message.includes('thanks')) {
                return 'You\'re welcome! Is there anything else I can help you with regarding our AI solutions?';
            }
            // Default response
            else {
                return 'Thank you for your message. I\'m an AI assistant with limited capabilities. For more specific information about our ' +
                       'AI solutions, please contact our team directly or explore the relevant sections on our website.';
            }
        }

        // Submit with button click
        chatbotSubmitBtn.addEventListener('click', sendMessage);

        // Submit with Enter key
        chatbotInputField.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    }
});
