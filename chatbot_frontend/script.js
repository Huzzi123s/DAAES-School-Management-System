
/* ==================================
   DAAES CHATBOT - JAVASCRIPT
================================== */

// Flask Backend API
const API_URL = "http://127.0.0.1:5000/api/chat";

// HTML Elements
const chatForm = document.getElementById("chatForm");
const messageInput = document.getElementById("messageInput");
const sendBtn = document.getElementById("sendBtn");

const messagesContainer =
    document.getElementById("messagesContainer");

const welcomeSection =
    document.getElementById("welcomeSection");

const clearChatBtn =
    document.getElementById("clearChatBtn");


// ==================================
// CHAT SETTINGS
// ==================================

const MIN_TYPING_TIME = 800;
let isSending = false;


// ==================================
// ADD MESSAGE TO CHAT
// ==================================

function addMessage(message, sender) {

    const messageRow = document.createElement("div");
    messageRow.className = `message-row ${sender}`;

    const avatar = document.createElement("div");
    avatar.className = "message-avatar";

    avatar.textContent = sender === "user" ? "👤" : "🤖";

    const messageContent = document.createElement("div");
    messageContent.className = "message-content";

    // Safe text insertion
    messageContent.textContent = message;

    messageRow.appendChild(avatar);
    messageRow.appendChild(messageContent);

    messagesContainer.appendChild(messageRow);

    scrollToBottom();
}


// ==================================
// SCROLL CHAT TO BOTTOM
// ==================================

function scrollToBottom() {

    const chatArea = document.getElementById("chatArea");

    if (chatArea) {
        chatArea.scrollTo({
            top: chatArea.scrollHeight,
            behavior: "smooth"
        });
    }
}


// ==================================
// SHOW TYPING ANIMATION
// ==================================

function showTypingIndicator() {

    removeTypingIndicator();

    const row = document.createElement("div");

    row.className = "message-row bot";
    row.id = "typingIndicator";

    const avatar = document.createElement("div");

    avatar.className = "message-avatar";
    avatar.textContent = "🤖";

    const typing = document.createElement("div");

    typing.className = "typing-indicator";

    typing.innerHTML = `
        <span></span>
        <span></span>
        <span></span>
    `;

    row.appendChild(avatar);
    row.appendChild(typing);

    messagesContainer.appendChild(row);

    scrollToBottom();
}


// ==================================
// REMOVE TYPING ANIMATION
// ==================================

function removeTypingIndicator() {

    const indicator =
        document.getElementById("typingIndicator");

    if (indicator) {
        indicator.remove();
    }
}


// ==================================
// SEND MESSAGE TO FLASK BACKEND
// ==================================

async function sendMessage(message) {

    if (!message.trim() || isSending) {
        return;
    }

    isSending = true;

    const typingStartTime = Date.now();

    welcomeSection.style.display = "none";

    addMessage(message, "user");

    messageInput.value = "";

    sendBtn.disabled = true;

    showTypingIndicator();

    try {

        const response = await fetch(API_URL, {

            method: "POST",

            headers: {
                "Content-Type": "application/json"
            },

            body: JSON.stringify({
                message: message
            })

        });

        const data = await response.json();

        if (!response.ok || !data.success) {

            throw new Error(
                data.reply || "Unable to process your request."
            );

        }

        // Minimum typing animation duration
        const elapsedTime = Date.now() - typingStartTime;

        const remainingTime = Math.max(
            0,
            MIN_TYPING_TIME - elapsedTime
        );

        await new Promise(resolve =>
            setTimeout(resolve, remainingTime)
        );

        removeTypingIndicator();

        addMessage(data.reply, "bot");

    } catch (error) {

        const elapsedTime = Date.now() - typingStartTime;

        const remainingTime = Math.max(
            0,
            MIN_TYPING_TIME - elapsedTime
        );

        await new Promise(resolve =>
            setTimeout(resolve, remainingTime)
        );

        removeTypingIndicator();

        console.error("Chatbot Error:", error);

        addMessage(
            "Sorry, I couldn't connect to the chatbot server. Please try again.",
            "bot"
        );

    } finally {

        isSending = false;

        sendBtn.disabled = false;

        messageInput.focus();

    }
}


// ==================================
// FORM SUBMISSION
// ==================================

chatForm.addEventListener("submit", function(event) {

    event.preventDefault();

    const message = messageInput.value.trim();

    if (!message) {
        return;
    }

    sendMessage(message);

});


// ==================================
// QUICK QUESTION BUTTONS
// ==================================

document.querySelectorAll(".suggestion-btn").forEach(button => {

    button.addEventListener("click", function() {

        const message = this.dataset.message;

        sendMessage(message);

    });

});


// ==================================
// CLEAR CHAT
// ==================================

clearChatBtn.addEventListener("click", function() {

    // Don't clear while a reply is processing
    if (isSending) {
        return;
    }

    messagesContainer.innerHTML = "";

    welcomeSection.style.display = "block";

    messageInput.value = "";

    messageInput.focus();

});


// ==================================
// INITIALIZATION
// ==================================

console.log(
    "DAAES Chatbot Frontend Loaded Successfully."
);
