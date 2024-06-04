<?php

require __DIR__ . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

class WebSocketServer implements MessageComponentInterface
{
    protected $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        echo "Message received: {$msg}\n";
        $data = json_decode($msg, true);

        if (isset($data['time'])) {
            // Broadcast the message to all connected clients
            foreach ($this->clients as $client) {
               // if ($from !== $client) {
                //打印当前时间戳，精确到毫秒
                echo "[" . date('Y-m-d H:i:s', time()) . "."
                    . substr(microtime(true), 11, 3) . "]" . "Send: {$msg}\n";
                $client->send(json_encode(['time' => $data['time']]));
               // }
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new WebSocketServer()
        )
    ),
    8080
);

echo "WebSocket server started on ws://localhost:8080\n";
$server->run();