// Minimal Vue 3 floating chatbot with custom CSS
const { createApp, ref, onMounted, watch, nextTick } = Vue;

const chatbotRoot = document.createElement('div');
chatbotRoot.id = 'nlcb-chatbot-portal-root';
chatbotRoot.className = 'nlcb-chatbot-portal-root'; // Add class for styling the portal root
document.body.appendChild(chatbotRoot);

createApp({
    setup() {
        const open = ref(false);
        const userInput = ref('');
        const messages = ref([
            { role: 'bot', text: 'Hi! How can I help you today?' }
        ]);
        const loading = ref(false);
        const faqs = ref([]); // Keep fetching FAQs in case we need them later, but don't display
        const messagesContainer = ref(null); // Ref for the messages div

        // Appearance settings (reactive)
        const appearance = Vue.reactive(nlcbChatbot.appearance || {});
        const title = Vue.computed(() => appearance.title || 'AI Chatbot');
        const bubbleIcon = Vue.computed(() => appearance.bubble_icon || '✨');
        const titlebarColor = Vue.computed(() => appearance.titlebar_color || '#0073aa');
        const botBubbleColor = Vue.computed(() => appearance.bot_bubble_color || '#e3f1fa');
        const userBubbleColor = Vue.computed(() => appearance.user_bubble_color || '#d1e7dd');

        // Fetch FAQs on mount
        fetch(nlcbChatbot.restUrl + 'faqs')
            .then(r => r.json())
            .then(data => { faqs.value = data; });

        function sendMessage() {
            if (!userInput.value.trim()) return;
            const question = userInput.value;
            messages.value.push({ role: 'user', text: question });
            userInput.value = '';
            loading.value = true;
            fetch(nlcbChatbot.restUrl + 'chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nlcbChatbot.nonce
                },
                body: JSON.stringify({ question })
            })
            .then(r => r.json())
            .then(data => {
                messages.value.push({ role: 'bot', text: data.answer });
            })
            .finally(() => { loading.value = false; });
        }

        // Watch for messages changes and scroll to bottom
        watch(messages.value, () => {
            nextTick(() => {
                if (messagesContainer.value) {
                    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
                }
            });
        });

        // Optional: Prevent body scroll when chat is open (for mobile)
        onMounted(() => {
            const body = document.body;
            watchEffect(() => {
                if (open.value) {
                    body.style.overflow = 'hidden';
                } else {
                    body.style.overflow = '';
                }
            });
        });

        return { open, userInput, messages, loading, faqs, sendMessage, messagesContainer, title, bubbleIcon, titlebarColor, botBubbleColor, userBubbleColor };
    },
    template: `
    <div class="nlcb-chatbot-portal-root">
        <button v-if="!open" @click="open = true" class="nlcb-chat-bubble">
            <span v-html="bubbleIcon"></span>
        </button>
        <div v-if="open" class="nlcb-chat-window">
            <div class="nlcb-chat-header" :style="{ background: titlebarColor, color: '#fff' }">
                <span>{{ title }}</span>
                <button @click="open = false">×</button>
            </div>
            <div class="nlcb-chat-messages" ref="messagesContainer">
                <div v-for="(msg, i) in messages" :key="i"
                    :class="msg.role === 'bot' ? 'nlcb-msg-bot' : 'nlcb-msg-user'"
                    :style="msg.role === 'bot' ? { background: botBubbleColor } : { background: userBubbleColor }"
                >
                    {{ msg.text }}
                </div>
                <div v-if="loading" class="nlcb-msg-bot nlcb-typing-indicator" :style="{ background: botBubbleColor }">
                    <span class="nlcb-typing-dot"></span>
                    <span class="nlcb-typing-dot"></span>
                    <span class="nlcb-typing-dot"></span>
                </div>
            </div>
            <form @submit.prevent="sendMessage" class="nlcb-chat-input">
                <input v-model="userInput" :disabled="loading" placeholder="Type your question..." />
                <button :disabled="loading || !userInput.trim()">Send</button>
            </form>
        </div>
    </div>
    `,
}).mount('#nlcb-chatbot-portal-root'); 