const sendBtn = document.getElementById("sendBtn");
const input = document.getElementById("userInput");
const messages = document.getElementById("messages");

sendBtn.addEventListener("click", sendMessage);

input.addEventListener("keypress", function (e) {
    if (e.key === "Enter") {
        sendMessage();
    }
});

async function sendMessage() {

    const message = input.value.trim();

    if (message === "") return;

    messages.innerHTML += `
        <div class="user-message">
            ${message}
        </div>
    `;

    input.value = "";

    const response = await fetch("../../backend/chat.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            message: message
        })
    });

    const data = await response.json();

    messages.innerHTML += `
        <div class="bot-message">
            ${data.reply}
        </div>
    `;

    messages.scrollTop = messages.scrollHeight;
}