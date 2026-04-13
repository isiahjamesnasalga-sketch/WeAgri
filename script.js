// Initialize Lucide Icons
lucide.createIcons();

// Navbar Scroll Effect
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
    if (window.scrollY > 50) {
        navbar.classList.add('bg-weagri-bg/90', 'backdrop-blur-md', 'shadow-lg', 'py-4');
        navbar.classList.remove('bg-transparent', 'py-6');
    } else {
        navbar.classList.remove('bg-weagri-bg/90', 'backdrop-blur-md', 'shadow-lg', 'py-4');
        navbar.classList.add('bg-transparent', 'py-6');
    }
});

// Mobile Menu Toggle
const mobileToggle = document.getElementById('mobile-toggle');
const mobileMenu = document.getElementById('mobile-menu');
const mobileMenuIcon = document.getElementById('mobile-menu-icon');

mobileToggle.addEventListener('click', () => {
    mobileMenu.classList.toggle('hidden');
    mobileMenu.classList.toggle('flex');
    
    // Toggle icon between Menu and X
    if (mobileMenu.classList.contains('hidden')) {
        mobileMenuIcon.setAttribute('data-lucide', 'menu');
    } else {
        mobileMenuIcon.setAttribute('data-lucide', 'x');
    }
    lucide.createIcons();
});

// Close mobile menu when clicking a link
document.querySelectorAll('#mobile-menu a').forEach(link => {
    link.addEventListener('click', () => {
        mobileMenu.classList.add('hidden');
        mobileMenu.classList.remove('flex');
        mobileMenuIcon.setAttribute('data-lucide', 'menu');
        lucide.createIcons();
    });
});

// Scroll Animations (Intersection Observer)
const observerOptions = {
    threshold: 0.1,
    rootMargin: "0px 0px -50px 0px"
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('opacity-100', 'translate-y-0');
            entry.target.classList.remove('opacity-0', 'translate-y-8');
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

document.querySelectorAll('.animate-on-scroll').forEach(el => {
    // Set initial state
    el.classList.add('opacity-0', 'translate-y-8', 'transition-all', 'duration-700', 'ease-out');
    observer.observe(el);
});

// Chatbot Logic
const chatToggle = document.getElementById('chat-toggle');
const chatWindow = document.getElementById('chat-window');
const closeChat = document.getElementById('close-chat');
const chatMessages = document.getElementById('chat-messages');
const chatInput = document.getElementById('chat-input');
const chatSend = document.getElementById('chat-send');
const tryAiBtn = document.getElementById('try-ai-btn');
const typingIndicator = document.getElementById('typing-indicator');

function openChat() {
    chatWindow.classList.remove('hidden');
    chatWindow.classList.add('flex');
    chatToggle.classList.add('hidden');
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function hideChat() {
    chatWindow.classList.add('hidden');
    chatWindow.classList.remove('flex');
    chatToggle.classList.remove('hidden');
}

chatToggle.addEventListener('click', openChat);
tryAiBtn.addEventListener('click', (e) => {
    e.preventDefault();
    openChat();
});
closeChat.addEventListener('click', hideChat);

function addMessage(text, sender) {
    const div = document.createElement('div');
    div.className = `flex ${sender === 'user' ? 'justify-end' : 'justify-start'} mb-4`;
    
    const innerDiv = document.createElement('div');
    innerDiv.className = `max-w-[85%] p-3 rounded-2xl text-sm ${
        sender === 'user' 
        ? 'bg-weagri-primary text-white rounded-tr-sm' 
        : 'bg-[#3a4b5e] text-gray-100 rounded-tl-sm'
    }`;
    innerDiv.textContent = text;
    
    div.appendChild(innerDiv);
    chatMessages.insertBefore(div, typingIndicator);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

chatSend.addEventListener('click', async () => {
    const text = chatInput.value.trim();
    if (!text) return;
    
    // Add user message
    addMessage(text, 'user');
    chatInput.value = '';
    
    // Show typing indicator
    typingIndicator.classList.remove('hidden');
    typingIndicator.classList.add('flex');
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    // ==========================================
    // PHP / MYSQL BACKEND CONNECTION POINT
    // ==========================================
    // When you move this to XAMPP, replace the setTimeout below 
    // with an actual fetch request to your PHP backend.
    //
    // Example:
    // try {
    //     const response = await fetch('api/chat.php', {
    //         method: 'POST',
    //         headers: { 'Content-Type': 'application/json' },
    //         body: JSON.stringify({ message: text })
    //     });
    //     const data = await response.json();
    //     typingIndicator.classList.add('hidden');
    //     typingIndicator.classList.remove('flex');
    //     addMessage(data.reply, 'ai');
    // } catch (error) {
    //     console.error('Error:', error);
    // }
    // ==========================================

    // Mock response for the static HTML version
    setTimeout(() => {
        typingIndicator.classList.add('hidden');
        typingIndicator.classList.remove('flex');
        addMessage("This is a mock response. When you move this to XAMPP, you can connect this chat interface to your PHP backend and MySQL database using fetch() in script.js!", 'ai');
    }, 1500);
});

chatInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') chatSend.click();
});
