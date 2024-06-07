<?php

namespace App\WebSocket;

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
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";

        $startTime = time();
        $interval = 30;
        $duration = 600;

        $timer = $this->loop->addPeriodicTimer($interval, function (TimerInterface $timer) use ($conn, $startTime, $interval, $duration) {
            $currentTime = time();
            if ($currentTime - $startTime < $duration) {
                $timestamp = date('Y-m-d H:i:s', $currentTime) . '.' . sprintf('%03d', (microtime(true) - floor(microtime(true))) * 1000);
                $message = json_encode(['time' => $timestamp]);
                echo "Sending: {$message}\n";
                $conn->send($message);
            } else {
                $this->loop->cancelTimer($timer);
            }
        });
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        echo "Message received: {$msg}\n";
        $data = json_decode($msg, true);

        if (isset($data['time'])) {
            foreach ($this->clients as $client) {
                echo "[" . date('Y-m-d H:i:s', time()) . "." . sprintf('%03d', (microtime(true) - floor(microtime(true))) * 1000) . "] Send: {$msg}\n";
                $client->send(json_encode(['time' => $data['time']]));
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    public static function run()
    {
        $loop = LoopFactory::create();
        $webSocketServer = new self($loop);

        $socket = new \React\Socket\SocketServer('0.0.0.0:8080', [], $loop);
        $http = new HttpServer(new WsServer($webSocketServer));
        $server = new IoServer($http, $socket, $loop);

        echo "WebSocket server started on ws://localhost:8080\n";
        $loop->run();
    }
}