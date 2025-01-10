document.addEventListener("DOMContentLoaded", function () {
    const chatForm = document.getElementById("chat-form");
    const chatInput = document.getElementById("chat-input");
    const chatBox = document.getElementById("chat-box");
    const uploadBtn = document.getElementById("upload-btn");
    const resourceInput = document.getElementById("resource-input");
    const contextMenu = document.getElementById("context-menu");

    const appendMessage = (msg) => {
        const isDeleted = msg.type === "resource" && !msg.file_url;
        const messageHTML = `
        <div class="message ${msg.type} ${isDeleted ? 'deleted' : ''}" data-resource-id="${msg.resource_id || ''}">
            <strong>${msg.username}:</strong> 
            ${msg.type === "resource"
                ? msg.file_url
                    ? `<a href="${msg.file_url}" target="_blank">${msg.content || 'File'}</a>`
                    : `<span>${msg.content || 'File'}</span>`
                : msg.content
            }
            <small>(${new Date(msg.timestamp).toLocaleString()})</small>
        </div>`;
        chatBox.insertAdjacentHTML("beforeend", messageHTML);
        chatBox.scrollTop = chatBox.scrollHeight;
    };

    const loadMessages = async () => {
        try {
            const response = await fetch(`fetch_messages.php?group_id=${groupId}`);
            const messages = await response.json();
            chatBox.textContent = ""; // Clear chat box
            messages.forEach(appendMessage);
        } catch {
            alert("Error loading messages.");
        }
    };

    loadMessages();

    const socket = new WebSocket(`${webSocketUrl}?group_id=${groupId}&user_id=${userId}`);

    socket.onmessage = (event) => {
        const msg = JSON.parse(event.data);

        if (msg.type === "delete_resource") {
            const resourceElement = document.querySelector(
                `.message[data-resource-id="${msg.resource_id}"]`
            );
            if (resourceElement) {
                resourceElement.remove();
            }
        } else {
            appendMessage(msg);
        }
    };

    chatForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        const message = chatInput.value.trim();
        if (!message) return;

        try {
            const response = await fetch("send_message.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `group_id=${groupId}&user_id=${userId}&message=${encodeURIComponent(message)}`,
            });

            const result = await response.json();
            if (result.status === "success") {
                socket.send(
                    JSON.stringify({
                        type: "message",
                        group_id: groupId,
                        user_id: userId,
                        username,
                        content: message,
                        timestamp: new Date().toISOString(),
                    })
                );
                chatInput.value = ""; // Clear input
            }
        } catch {
            alert("Failed to send message.");
        }
    });

    uploadBtn.addEventListener("click", async () => {
        const file = resourceInput.files[0];
        if (!file) return alert("Select a file to upload.");

        const formData = new FormData();
        formData.append("group_id", groupId);
        formData.append("resource", file);

        try {
            const response = await fetch("upload_resource.php", {
                method: "POST",
                body: formData,
            });

            const result = await response.json();
            if (result.status === "success") {
                socket.send(
                    JSON.stringify({
                        type: "resource",
                        group_id: groupId,
                        user_id: userId,
                        username,
                        content: result.file_name,
                        file_url: result.file_url,
                        resource_id: result.resource_id,
                        timestamp: new Date().toISOString(),
                    })
                );
                resourceInput.value = ""; // Clear the file input
            } else {
                alert(result.message);
            }
        } catch {
            alert("Failed to upload resource.");
        }
    });

    chatBox.addEventListener("contextmenu", (event) => {
        event.preventDefault();
        const resourceElement = event.target.closest(".message[data-resource-id]");

        // Check if it's a resource and not deleted
        if (resourceElement && !resourceElement.classList.contains("deleted") && resourceElement.classList.contains("resource")) {
            const resourceId = resourceElement.getAttribute("data-resource-id");

            // Show the context menu
            contextMenu.style.display = "block";
            contextMenu.style.left = `${event.pageX}px`;
            contextMenu.style.top = `${event.pageY}px`;

            // Add delete functionality
            const deleteButton = document.getElementById("delete-resource-btn");
            deleteButton.onclick = async () => {
                if (confirm("Are you sure you want to delete this resource?")) {
                    try {
                        const response = await fetch("delete_resource.php", {
                            method: "POST",
                            headers: { "Content-Type": "application/x-www-form-urlencoded" },
                            body: `group_id=${groupId}&resource_id=${resourceId}`,
                        });

                        const result = await response.json();
                        if (result.status === "success") {
                            socket.send(
                                JSON.stringify({
                                    type: "delete_resource",
                                    group_id: groupId,
                                    resource_id: resourceId,
                                })
                            );
                            contextMenu.style.display = "none";
                        } else {
                            alert(result.message);
                        }
                    } catch {
                        alert("Failed to delete resource.");
                    }
                }
            };
        } else {
            // Hide context menu if not a resource or if it's deleted
            contextMenu.style.display = "none";
        }
    });

    // Hide the context menu on any click outside
    document.addEventListener("click", () => {
        contextMenu.style.display = "none";
    });
});
