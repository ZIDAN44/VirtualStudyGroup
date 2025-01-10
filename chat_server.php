<?php
require 'vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ChatServer implements MessageComponentInterface {
    private $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn) {
        // Parse query parameters from the connection URI
        $queryParams = [];
        parse_str($conn->httpRequest->getUri()->getQuery(), $queryParams);

        $conn->group_id = $queryParams['group_id'] ?? null;
        $conn->user_id = $queryParams['user_id'] ?? null;

        if ($conn->group_id && $conn->user_id) {
            $this->clients->attach($conn);
            echo "New connection: User {$conn->user_id} in Group {$conn->group_id}\n";
        } else {
            echo "Invalid connection parameters.\n";
            $conn->close();
        }
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);

        if (!isset($data['type'], $data['group_id']) || !in_array($data['type'], ['message', 'resource', 'delete_resource'], true)) {
            echo "Invalid message format or type.\n";
            return;
        }

        echo "Broadcasting message:\n" . json_encode($data, JSON_PRETTY_PRINT) . "\n";

        // Broadcast the message to all clients in the same group
        foreach ($this->clients as $client) {
            if ((int)$client->group_id === (int)$data['group_id']) {
                $client->send(json_encode($data));
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection closed: User {$conn->user_id} in Group {$conn->group_id}\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Initialize the server
$server = \Ratchet\Server\IoServer::factory(
    new \Ratchet\Http\HttpServer(
        new \Ratchet\WebSocket\WsServer(
            new ChatServer()
        )
    ),
    8080
);

$server->run();
