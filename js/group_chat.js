document.addEventListener("DOMContentLoaded", function () {
    const chatForm = document.getElementById("chat-form");
    const chatInput = document.getElementById("chat-input");
    const chatBox = document.getElementById("chat-box");

    // Function to fetch and load messages
    function loadMessages() {
        fetch("fetch_messages.php?group_id=" + groupId)
            .then(response => response.text())
            .then(data => {
                chatBox.innerHTML = data;
                chatBox.scrollTop = chatBox.scrollHeight; // Scroll to the bottom
            })
            .catch(error => console.error("Error fetching messages:", error));
    }

    // Load messages initially and every 5 seconds
    loadMessages();
    setInterval(loadMessages, 5000);

    // Handle message submission
    chatForm.addEventListener("submit", function (event) {
        event.preventDefault();
        
        const message = chatInput.value;
        if (!message) return;

        // Send the message to the server via AJAX
        fetch("send_message.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `group_id=${groupId}&user_id=${userId}&message=${encodeURIComponent(message)}`
        })
        .then(response => response.text())
        .then(data => {
            chatInput.value = ""; // Clear input
            loadMessages(); // Reload messages
        })
        .catch(error => console.error("Error sending message:", error));
    });
});
