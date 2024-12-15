document.addEventListener("DOMContentLoaded", function () {
    const chatForm = document.getElementById("chat-form");
    const chatInput = document.getElementById("chat-input");
    const chatBox = document.getElementById("chat-box");

    // Async function to fetch and display chat messages and resources
    async function loadMessagesAndResources() {
        try {
            const response = await fetch(`fetch_messages.php?group_id=${groupId}`);
            const data = await response.text();
            chatBox.innerHTML = data;
            chatBox.scrollTop = chatBox.scrollHeight; // Scroll to the bottom
        } catch (error) {
            alert("Error fetching messages and resources.");
        }
    }

    // Load messages and resources initially and every 5 seconds
    loadMessagesAndResources();
    setInterval(loadMessagesAndResources, 5000);

    // Handle message submission
    chatForm.addEventListener("submit", async function (event) {
        event.preventDefault();

        const message = chatInput.value.trim();
        if (!message) return;

        try {
            const response = await fetch("send_message.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: `group_id=${groupId}&user_id=${userId}&message=${encodeURIComponent(message)}`,
            });
            chatInput.value = "";
            await loadMessagesAndResources();
        } catch (error) {
            alert("Error sending message.");
        }
    });
});
