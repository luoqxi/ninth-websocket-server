<?php

require __DIR__ . '/vendor/autoload.php';

\Ratchet\Client\connect('ws://localhost:8080')->then(function($conn) {
    $conn->on('message', function($msg) use ($conn) {
        //打印当前时间戳
        echo "[" . date('Y-m-d H:i:s', time()) . "." . substr(microtime(true), 11, 3) . "] " . "Received: {$msg}\n";
        $conn->close();
    });

    $conn->send(json_encode(['time' => '2024-06-03 12:00:00']));
}, function ($e) {
    echo "Could not connect: {$e->getMessage()}\n";
});