<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<?php $this->load->view('front-end/' . THEME . '/include-css', $data); ?>
<?php $this->load->view('front-end/' . THEME . '/include-script'); ?>

<style>

/* MAIN BOX */
.chat-ui {
    background: #fff;
    border-radius: 18px;
    padding: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

/* HEADER */
.chat-header {
    font-weight: 600;
    margin-bottom: 12px;
    color: #333;
}

/* CHAT AREA */
.chat-box {
    max-height: 200px;
    overflow-y: auto;
    margin-bottom: 10px;
}

/* BOT MSG */
.bot-msg {
    background: #f8f8f8;
    padding: 10px;
    border-radius: 12px;
    margin-bottom: 8px;
    max-width: 80%;
}

/* USER MSG */
.user-msg {
    background: #ffe9cc;
    padding: 10px;
    border-radius: 12px;
    margin-bottom: 8px;
    margin-left: auto;
    max-width: 80%;
}

/* MESSAGE INTRO */
.chat-message {
    background: #f8f8f8;
    padding: 12px;
    border-radius: 12px;
    margin-bottom: 10px;
}

/* BUTTONS */
.chat-row {
    display: flex;
    gap: 10px;
    margin-top: 8px;
}

.chat-btn {
    flex: 1;
    padding: 10px;
    border-radius: 10px;
    background: #fff;
    border: 1px solid #eee;
    cursor: pointer;
    transition: all 0.25s ease;
    font-size: 13px;
}

/* 🔥 PREMIUM HOVER */
.chat-btn:hover {
    background: linear-gradient(135deg, #fff4e5, #ffe0b3);
    border-color: #f4a742;
    color: #b96d00;
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(244,167,66,0.25);
}

/* POPULAR */
.popular-title {
    font-weight: 600;
    margin-top: 10px;
}

/* INPUT */
.chat-input {
    display: flex;
    gap: 8px;
}

.chat-input input {
    width: 100%;
    padding: 10px;
    border-radius: 20px;
    border: 1px solid #ddd;
}

/* SEND BUTTON (added only) */
#sendBtn {
    background: #f4a742;
    border: none;
    color: white;
    padding: 10px 16px;
    border-radius: 20px;
    cursor: pointer;
    transition: 0.3s;
}

#sendBtn:hover {
    background: #e6952f;
}

/* TYPING */
.typing {
    opacity: 0.6;
    font-style: italic;
}

/* ANIMATION */
.bot-msg, .user-msg {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {opacity:0; transform:translateY(10px);}
    to {opacity:1; transform:translateY(0);}
}

</style>

</head>

<body>

<div class="container mt-3">

<div class="chat-ui">

<!-- HEADER -->
<div class="chat-header">
🤖 E-Shop Assistant ●
</div>

<!-- CHAT BOX -->
<div id="chat-box" class="chat-box">
    <div class="bot-msg">Hi 👋 I’m here to help. What can I assist you with today?</div>
</div>

<!-- MESSAGE -->
<div class="chat-message">
Choose an option below or type your question
</div>

<!-- ACTIONS -->
<div class="chat-actions">

<div class="chat-row">
<div class="chat-btn" data-chat-action="track_order" data-chat-message="track order">📦 Track Order</div>
<div class="chat-btn" data-chat-action="cancel_order" data-chat-message="cancel order">❌ Cancel Order</div>
</div>

<div class="chat-row">
<div class="chat-btn" data-chat-action="return_item" data-chat-message="return item">🔄 Return Item</div>
<div class="chat-btn" data-chat-action="payment_issue" data-chat-message="payment issue">💳 Payment Issue</div>
</div>

<div class="chat-row">
<div class="chat-btn" data-chat-action="product_inquiry" data-chat-message="product inquiry">🛍️ Product Inquiry</div>
<div class="chat-btn" onclick="openWhatsApp()">🎧 WhatsApp Support </div>
</div>

</div>

<!-- POPULAR -->
<div class="chat-popular">

<div class="popular-title">Popular Questions</div>

<div class="chat-row">
<div class="chat-btn" data-chat-action="track_order" data-chat-message="where is my order">📦 Where is my order?</div>
    <div class="chat-btn" data-chat-action="return_item" data-chat-message="start return">🔄 How do I return?</div>
</div>
<div class="chat-btn" data-chat-action="payment_issue" data-chat-message="payment problem">
💳 I need help with a payment problem
</div>

</div>

<!-- INPUT (ONLY UPDATED PART) -->
<div class="chat-input">
    <input id="userInput" placeholder="Type a message..." />
    <button id="sendBtn">Send</button>
</div>

</div>

<!-- JS -->
<script>

// =====================
// MAIN SEND FUNCTION
// =====================
let pendingAction = "";
function appendMessage(className, text) {
    let chatBox = document.getElementById("chat-box");

    if (!chatBox) {
        console.log("❌ chat-box not found");
        return null ;
    }

    // USER MESSAGE
    
    let message = document.createElement("div");
    message.className = className;
    message.innerText = text;
    chatBox.appendChild(message);
    chatBox.scrollTop = chatBox.scrollHeight;

    // TYPING INDICATOR
   return message;
}
function getPromptForAction(action) {
    const prompts = {
        track_order: "Sure — please enter your Order ID so I can estimate how many days are left for delivery.",
        cancel_order: "I can guide you through cancellation. If your order is already shipped, please share the Order ID with support for a manual check.",
        return_item: "Returns are handled from My Account → My Orders → Return. Tell me what went wrong with the item if you need extra help.",
        payment_issue: "Payment issue noted. Please share whether money was debited, the payment method, and any payment reference/order ID.",
        product_inquiry: "Please send the product name or product link, and I’ll help with availability, variants, or delivery questions.",
        support: "Click WhatsApp Support to chat directly with our support team."
    };

    return prompts[action] || "Please type your question and I’ll help.";
}

function sendChatRequest(text, action, orderId) {
    let typing = appendMessage("bot-msg typing", "Typing...");

    const params = new URLSearchParams();
    params.append("message", text || "");

    if (action) {
        params.append("action", action);
    }

    // =====================
    // API CALL
    // =====================
    if (action === "track_order" && orderId) {
        params.append("order_id", orderId);
    }
    console.log("Sending:");
    console.log(params.toString());
    fetch("<?= base_url('chat/send') ?>", {
    method: "POST",
    headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        "Accept": "application/json"
    },
    body: params.toString()
    })
    .then(res => res.text()) 
    .then(data => {

        console.log("RAW RESPONSE:", data);

        if (typing) {
            typing.remove();
        }

        try {
            let json = JSON.parse(data);
            appendMessage("bot-msg", json.reply || "No response");

        } catch (e) {
            console.log("❌ JSON ERROR:", e);
            appendMessage("bot-msg", "Server error ⚠️");

        }
    })
    .catch(err => {

        console.log("❌ FETCH ERROR:", err);
        appendMessage("bot-msg", "Connection error ⚠️");


    });
}

//Whatsapp direct connection
function openWhatsApp()
{
    window.open(
        "https://wa.me/917290024349?text=Hello%20Cretzo%20Support",
        "_blank"
    );
}




// =====================
// BUTTON + ENTER SUPPORT
// =====================
window.sendMsg = function (text, action) {
    text = (text || "").trim();
    action = action || "";
    if (!text && !action) {
        return;
    }

    pendingAction = "";
    appendMessage("user-msg", text || getPromptForAction(action));

    if (action === "track_order") {
        pendingAction = "track_order";
    }

    sendChatRequest(text, action, "");
};

document.addEventListener("DOMContentLoaded", function () {
    let input = document.getElementById("userInput");
    let sendBtn = document.getElementById("sendBtn");

    if (!input || !sendBtn) {
        console.log("❌ Input or Button missing");
        return;
    }

    // CLICK SEND
    document.querySelectorAll("[data-chat-message]").forEach(function (button) {
        button.addEventListener("click", function () {
            pendingAction = "";
            window.sendMsg(button.dataset.chatMessage, button.dataset.chatAction || "");
        });
    });
    sendBtn.addEventListener("click", function () {

        let text = input.value.trim();
        if (text === "") {
            return;
        }

        if (pendingAction === "track_order") {
            appendMessage("user-msg", text);
            sendChatRequest(text, "track_order", text);
            pendingAction = "";
        } else {
            window.sendMsg(text, "");
        
        

      
        }input.value = "";
    });

    // ENTER KEY
    input.addEventListener("keypress", function (e) {

        if (e.key === "Enter") {
            e.preventDefault();
            sendBtn.click();
        }
    });

});

</script>

</body>
</html>