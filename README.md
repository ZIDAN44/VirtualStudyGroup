
# Real-Time Chat Server with Ratchet

A real-time WebSocket-based chat server built using PHP and [Ratchet](http://socketo.me/). This server enables group-based messaging with real-time updates, supporting structured JSON message formats.

---

## Features

- **Real-Time Communication**: Broadcast messages instantly to all users in the same group.
- **Group Chat Support**: Users can join specific groups and communicate within their group context.
- **Structured Messages**: Supports `message` and `resource` types for versatile communication.
- **Error Handling**: Robust error management to ensure smooth operation.

---

## Installation

### Prerequisites
- PHP 7.4 or higher
- Composer (Dependency Manager for PHP)

### Steps to Install
1. Clone the repository:
   ```bash
   git clone https://github.com/ZIDAN44/VirtualStudyGroup.git -b chat-server chat-server
   cd chat-server
   ```

2. Install dependencies:
   ```bash
   composer require cboden/ratchet
   ```

3. Run the server:
   ```bash
   php chat_server.php
   ```

---

## Usage

### Connecting to the Server
The server runs on port `8080` by default. Connect your WebSocket client to the following URL:
```
ws://localhost:8080?group_id={GROUP_ID}&user_id={USER_ID}
```

- Replace `{GROUP_ID}` with the group the user belongs to.
- Replace `{USER_ID}` with the user's unique identifier.

### Sending Messages
Send messages as structured JSON. Supported formats:
#### Example 1: Text Message
```json
{
    "type": "message",
    "group_id": 1,
    "user_id": 123,
    "username": "User1",
    "content": "Hello, Group!",
    "timestamp": "2025-01-09T19:01:46.924Z"
}
```

#### Example 2: Resource Sharing
```json
{
    "type": "resource",
    "group_id": 1,
    "user_id": 123,
    "username": "User1",
    "content": "resource.pdf",
    "file_url": "https://example.com/resource.pdf",
    "description": "Check out this resource!",
    "timestamp": "2025-01-09T19:01:46.924Z"
}
```

---

## Example WebSocket Client

Below is an example HTML page that can be used as a simple WebSocket client to test the `chat_server.php`. Copy the following code into an `.html` file and open it in your browser:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebSocket Chat Example</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        #messages {
            border: 1px solid #ccc;
            height: 300px;
            overflow-y: scroll;
            padding: 10px;
            margin-bottom: 10px;
        }
        #messageInput {
            width: calc(100% - 70px);
            padding: 5px;
        }
        #sendBtn {
            padding: 6px 10px;
        }
    </style>
</head>
<body>
    <h1>WebSocket Chat Example</h1>
    <div>
        <label for="group_id">Group ID:</label>
        <input type="text" id="group_id" placeholder="Enter Group ID">
        <label for="user_id">User ID:</label>
        <input type="text" id="user_id" placeholder="Enter User ID">
        <button id="connectBtn">Connect</button>
    </div>
    <div id="messages"></div>
    <div>
        <input type="text" id="messageInput" placeholder="Type your message here...">
        <button id="sendBtn">Send</button>
    </div>

    <script>
        let socket;

        document.getElementById('connectBtn').addEventListener('click', () => {
            const groupId = document.getElementById('group_id').value;
            const userId = document.getElementById('user_id').value;

            if (!groupId || !userId) {
                alert('Please enter both Group ID and User ID.');
                return;
            }

            const wsUrl = `ws://localhost:8080?group_id=${groupId}&user_id=${userId}`;
            socket = new WebSocket(wsUrl);

            socket.onopen = () => {
                console.log('Connected to WebSocket server');
                document.getElementById('messages').innerHTML += `<div>Connected as User ${userId} in Group ${groupId}</div>`;
            };

            socket.onmessage = (event) => {
                const message = JSON.parse(event.data);
                const messagesDiv = document.getElementById('messages');
                messagesDiv.innerHTML += `<div><strong>${message.type.toUpperCase()}:</strong> ${message.content}</div>`;
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
            };

            socket.onclose = () => {
                console.log('Disconnected from WebSocket server');
                document.getElementById('messages').innerHTML += `<div>Disconnected</div>`;
            };

            socket.onerror = (error) => {
                console.error('WebSocket error:', error);
                document.getElementById('messages').innerHTML += `<div>Error occurred</div>`;
            };
        });

        document.getElementById('sendBtn').addEventListener('click', () => {
            if (!socket || socket.readyState !== WebSocket.OPEN) {
                alert('You are not connected to the WebSocket server.');
                return;
            }

            const messageInput = document.getElementById('messageInput');
            const content = messageInput.value;

            if (!content.trim()) {
                alert('Please enter a message.');
                return;
            }

            const groupId = document.getElementById('group_id').value;
            const message = {
                type: 'message',
                group_id: groupId,
                content: content
            };

            socket.send(JSON.stringify(message));
            messageInput.value = '';
        });
    </script>
</body>
</html>
```

---

## Server Logging

- **New Connection**: Logs the user and group information upon connection.
- **Invalid Connections**: Logs attempts with missing or invalid `group_id` or `user_id`.
- **Message Broadcast**: Displays the JSON payload for all broadcast messages.
- **Connection Closure**: Logs user and group information upon disconnection.
- **Errors**: Logs any exceptions encountered during operation.

---

## Development

### Customization
- **Port**: Change the port by modifying the second parameter in the `IoServer::factory()` call.
- **Message Validation**: Extend or modify the `onMessage` method to support additional message types.

### Dependencies
- [cboden/ratchet](https://github.com/ratchetphp/Ratchet): The PHP WebSocket library powering this server.

---

## Contributing
Pull requests are welcome! For major changes, please open an issue first to discuss what you would like to change.

---

## License
This project is licensed under the MIT License. See the `LICENSE` file for more information.
