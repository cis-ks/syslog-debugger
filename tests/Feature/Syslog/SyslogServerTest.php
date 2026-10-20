<?php

use App\Events\SyslogMessageReceived;
use App\Services\Syslog\SyslogMessage;
use App\Services\Syslog\SyslogServer;
use Illuminate\Support\Facades\Event;
use React\EventLoop\StreamSelectLoop;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;

test('syslog server processes udp datagrams', function () {
    Event::fake([SyslogMessageReceived::class]);

    $loop = new StreamSelectLoop();
    $udpPort = 15514;

    $server = new SyslogServer(
        udpPort: $udpPort,
        tlsPort: 0,
        enableUdp: true,
        enableTls: false,
        loop: $loop,
    );

    $received = [];
    $server->onMessage(function (SyslogMessage $msg) use (&$received, $server, $loop) {
        $received[] = $msg;
        $server->stop();
        $loop->stop();
    });

    $server->start();

    // Send UDP packet via standard PHP stream
    $fp = stream_socket_client("udp://127.0.0.1:{$udpPort}", $errno, $errstr);
    if ($fp) {
        $rawMsg = "<34>Oct 11 22:14:15 mymachine su[123]: 'su root' failed for lonvick";
        fwrite($fp, $rawMsg);
        fclose($fp);
    }

    $loop->addTimer(0.2, function () use ($server, $loop) {
        $server->stop();
        $loop->stop();
    });

    $loop->run();

    expect($received)->toHaveCount(1)
        ->and($received[0]->appName)->toBe('su')
        ->and($received[0]->protocol)->toBe('udp');

    Event::assertDispatched(SyslogMessageReceived::class);
});

test('syslog server processes tcp stream messages', function () {
    Event::fake([SyslogMessageReceived::class]);

    $loop = new StreamSelectLoop();
    $tcpPort = 16514;

    $server = new SyslogServer(
        udpPort: 0,
        tlsPort: $tcpPort,
        enableUdp: false,
        enableTls: true,
        tlsOptions: [], // plaintext TCP in test
        loop: $loop,
    );

    $received = [];
    $server->onMessage(function (SyslogMessage $msg) use (&$received, $server, $loop) {
        $received[] = $msg;
        $server->stop();
        $loop->stop();
    });

    $server->start();

    $connector = new Connector([], $loop);
    $connector->connect("127.0.0.1:{$tcpPort}")->then(function (ConnectionInterface $conn) {
        $msg = "<165>1 2003-10-11T22:14:15.003Z mymachine.example.com evntslog 47 ID47 - An application event log entry\n";
        $conn->write($msg);
    });

    $loop->addTimer(0.5, function () use ($server, $loop) {
        $server->stop();
        $loop->stop();
    });

    $loop->run();

    expect($received)->toHaveCount(1)
        ->and($received[0]->appName)->toBe('evntslog')
        ->and($received[0]->protocol)->toBe('tls');

    Event::assertDispatched(SyslogMessageReceived::class);
});
