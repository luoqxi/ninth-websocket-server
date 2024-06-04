<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Ratchet\Client\WebSocket;
use Ratchet\Client\Connector;
use React\EventLoop\Factory;

class WebSocketController extends Controller
{
    public function sendMessage(Request $request)
    {
        $loop = Factory::create();
        $connector = new Connector($loop);

        $time = now()->addMinutes(10)->toDateTimeString(); // 发送10分钟后的时间

        $connector('ws://localhost:8080')->then(function(WebSocket $conn) use ($time) {
            $conn->send(json_encode(['time' => $time]));
            $conn->close();
        }, function(\Exception $e) use ($loop) {
            echo "Could not connect: {$e->getMessage()}\n";
            $loop->stop();
        });

        $loop->run();

        return response()->json(['message' => 'Data sent', 'time' => $time]);
    }
}