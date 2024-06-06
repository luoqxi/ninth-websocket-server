<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
//     die('Please run composer install first');
// }
require __DIR__ . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\TimerInterface;

class WebSocketServer implements MessageComponentInterface
{
    protected $clients;
    protected $loop;

    public function __construct($loop)
    {
        $this->clients = new \SplObjectStorage;
        $this->loop = $loop;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";

        // Schedule a task to send timestamps every 30 seconds for 10 minutes
        $startTime = time();
        $interval = 30;
        $duration = 600; // 10 minutes in seconds

        $timer = $this->loop->addPeriodicTimer($interval, function (TimerInterface $timer)
        use ($conn, $startTime, $interval, $duration) {
            $currentTime = time();
            if ($currentTime - $startTime < $duration) {
                // $timestamp = date('Y-m-d H:i:s', $currentTime) . '.' . substr(microtime(true), 11, 3); 不精确
                $timestamp = date('Y-m-d H:i:s', $currentTime) . '.'
                    . sprintf('%03d', (microtime(true) - floor(microtime(true))) * 1000);
                $message = json_encode(['time' => $timestamp]);
                echo "Sending: {$message}\n";
                $conn->send($message);
            } else {
                // Stop sending messages after 10 minutes
                $this->loop->cancelTimer($timer);
            }
        });
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        echo "Message received: {$msg}\n";
        $data = json_decode($msg, true);

        if (isset($data['time'])) {
            // Broadcast the message to all connected clients
            foreach ($this->clients as $client) {
                // Print current timestamp with milliseconds
                echo "[" . date('Y-m-d H:i:s', time()) . "."
                    . sprintf('%03d', (microtime(true) - floor(microtime(true)))
                        * 1000) . "] Send: {$msg}\n";
                $client->send(json_encode(['time' => $data['time']]));
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

// Create React event loop
$loop = LoopFactory::create();

// Pass the loop to the WebSocketServer instance
$webSocketServer = new WebSocketServer($loop);

$socket = new \React\Socket\SocketServer('0.0.0.0:8080', [], $loop);
$http = new HttpServer(new WsServer($webSocketServer));
$server = new IoServer($http, $socket, $loop);

echo "WebSocket server started on ws://localhost:8080\n";

// Run the loop
$loop->run();
