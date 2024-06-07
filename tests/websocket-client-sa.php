<?php

require __DIR__ . '/vendor/autoload.php';

\Ratchet\Client\connect('ws://localhost:8080')->then(function ($conn) {
    // 当连接成功时，发送初始消息
    $conn->send(json_encode(['time' => '2024-06-03 12:00:00']));

    // 监听服务器发送的消息
    $conn->on('message', function ($msg) use ($conn) {
        // 打印当前时间戳和接收到的消息
        echo "[" . date('Y-m-d H:i:s', time()) . "."
            . sprintf('%03d', (microtime(true) - floor(microtime(true)))
                * 1000) . "] " . "Received: {$msg}\n";
    });

    // 处理连接关闭事件
    $conn->on('close', function ($code = null, $reason = null) {
        echo "Connection closed ({$code} - {$reason})\n";
    });
}, function ($e) {
    echo "Could not connect: {$e->getMessage()}\n";
});
