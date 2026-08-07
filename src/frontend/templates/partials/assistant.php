<style>
/* --- Page Layout Styles --- */
body {
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
    background-color: #f4f6f8;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.site-header {
    background-color: #ffffff;
    padding: 15px 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.site-header .logo {
    font-weight: bold;
    font-size: 1.2rem;
    color: #333;
    text-decoration: none;
}

.site-header nav ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    gap: 20px;
}

.site-header nav a {
    text-decoration: none;
    color: #333;
    font-weight: bold;
}

.site-header nav a:hover {
    color: #d32f2f;
}

.main-content {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.site-footer {
    background-color: #333;
    color: #fff;
    text-align: center;
    padding: 15px;
    font-size: 0.9rem;
}

/* --- Chat Container Styles --- */
.chat-container {
    width: 100%;
    max-width: 600px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    display: flex;
    flex-direction: column;
    height: 650px;
    max-height: 80vh;
    overflow: hidden;
    border: 1px solid #e0e0e0;
}

.chat-header {
    background-color: #d32f2f;
    color: #ffffff;
    padding: 16px;
    font-size: 1.25rem;
    font-weight: bold;
    text-align: center;
    flex-shrink: 0;
}

.messages {
    flex: 1;
    padding: 16px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
    background-color: #f9f9f9;
}

.bot-message, .user-message {
    max-width: 85%;
    padding: 12px 16px;
    border-radius: 12px;
    line-height: 1.6;
    font-size: 0.95rem;
    word-break: break-word;
}

.bot-message {
    background-color: #ffffff;
    color: #333333;
    align-self: flex-start;
    border-bottom-left-radius: 2px;
    border: 1px solid #e0e0e0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.user-message {
    background-color: #d32f2f;
    color: #ffffff;
    align-self: flex-end;
    border-bottom-right-radius: 2px;
}

.chat-input {
    display: flex;
    padding: 12px;
    border-top: 1px solid #e0e0e0;
    background: #ffffff;
    gap: 8px;
    flex-shrink: 0;
}

.chat-input input {
    flex: 1;
    padding: 10px 14px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 1rem;
    outline: none;
}

.chat-input input:focus {
    border-color: #d32f2f;
}

.chat-input button {
    background-color: #d32f2f;
    color: #ffffff;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.2s ease;
}

.chat-input button:hover {
    background-color: #b71c1c;
}
</style>

<header class="site-header">
    <a href="/" class="logo">Pit o Cuixa</a>
    <nav>
        <ul>
            <li><a href="/">Inici</a></li>
            <li><a href="/carta">Carta</a></li>
        </ul>
    </nav>
</header>

<div class="main-content">
    <div class="chat-container">
        <div class="chat-header">
            🍗 Pit o Cuixa Assistant
        </div>

        <div id="messages" class="messages">
            <div class="bot-message">
                👋 ¡Hola! Bienvenido a <strong>Pit o Cuixa</strong>.<br><br>
                Puedo ayudarte con:<br>
                🍗 Nuestro menú y combos<br>
                💰 Precios actualizados<br>
                ⭐ Productos más vendidos<br>
                🕒 Horarios<br>
                🚚 Zonas de reparto<br><br>
                Escribe tu pregunta abajo 👇
            </div>
        </div>

        <div class="chat-input">
            <input
                type="text"
                id="userInput"
                placeholder="Escribe tu pregunta..."
                autocomplete="off"
            >
            <button id="sendBtn">
                Enviar
            </button>
        </div>
    </div>
</div>

<footer class="site-footer">
    <p>Pit o Cuixa | Pollería i rostería a Torredembarra | Horari: Dll-Dg 11:00–23:00</p>
</footer>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const userInput = document.getElementById('userInput');
    const sendBtn = document.getElementById('sendBtn');
    const messagesContainer = document.getElementById('messages');

    async function sendMessage() {
        const text = userInput.value.trim();
        if (!text) return;

        // 1. Render user message
        const userDiv = document.createElement('div');
        userDiv.className = 'user-message';
        userDiv.textContent = text;
        messagesContainer.appendChild(userDiv);

        userInput.value = '';
        messagesContainer.scrollTop = messagesContainer.scrollHeight;

        // 2. Render temporary loading indicator
        const botDiv = document.createElement('div');
        botDiv.className = 'bot-message';
        botDiv.textContent = 'Escribiendo...';
        messagesContainer.appendChild(botDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;

try {
            // 3. Request router API endpoint (POST — message goes in the body)
            const response = await fetch('/api/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message: text })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }

            const data = await response.json();
            let textReply = data.reply || data.message || 'Lo siento, no pude procesar la solicitud.';

            // Parse simple Markdown bolding and line breaks to HTML
            textReply = textReply.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            textReply = textReply.replace(/\n/g, '<br>');

            botDiv.innerHTML = textReply;
        } catch (error) {
            console.error('Fetch error details:', error);
            botDiv.textContent = 'Error al conectar con el servidor.';
        }

        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    sendBtn.addEventListener('click', sendMessage);

    userInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
});
</script>