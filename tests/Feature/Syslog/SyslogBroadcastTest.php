<?php

use App\Events\SyslogMessageReceived;
use App\Services\Syslog\SyslogMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Facades\Event;

test('syslog message received event implements should broadcast now', function () {
    $msg = new SyslogMessage(
        priority: 13,
        facility: 1,
        severity: 5,
        facilityName: 'user',
        severityName: 'Notice',
        timestamp: '2026-10-20T12:00:00Z',
        hostname: 'web01',
        appName: 'app',
        processId: '123',
        messageId: 'ID1',
        structuredData: null,
        message: 'Broadcast test message',
        raw: '<13>1 2026-10-20T12:00:00Z web01 app 123 ID1 - Broadcast test message',
        sourceIp: '192.168.1.10',
        protocol: 'udp',
    );

    $event = new SyslogMessageReceived($msg);

    expect($event)->toBeInstanceOf(ShouldBroadcastNow::class);

    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(Channel::class);
    expect($channels[0]->name)->toBe('syslog');

    expect($event->broadcastAs())->toBe('SyslogMessageReceived');

    $payload = $event->broadcastWith();
    expect($payload)->toHaveKeys([
        'priority',
        'facility',
        'severity',
        'facility_name',
        'severity_name',
        'timestamp',
        'hostname',
        'app_name',
        'process_id',
        'message_id',
        'message',
        'raw',
        'source_ip',
        'protocol',
        'received_at',
        'attributes',
        'key_value_pairs',
    ]);
    expect($payload['message'])->toBe('Broadcast test message');
    expect($payload['hostname'])->toBe('web01');
    expect($payload['severity_name'])->toBe('Notice');
    expect($payload['facility_name'])->toBe('user');
    expect($payload['protocol'])->toBe('udp');
});

test('syslog event is dispatched when event is fired', function () {
    Event::fake([SyslogMessageReceived::class]);

    $msg = new SyslogMessage(
        priority: 34,
        facility: 4,
        severity: 2,
        facilityName: 'auth',
        severityName: 'Critical',
        timestamp: '2026-10-20T12:00:00Z',
        hostname: 'auth-server',
        appName: 'sshd',
        processId: '999',
        messageId: null,
        structuredData: null,
        message: 'Failed root login',
        raw: '<34>Oct 20 12:00:00 auth-server sshd[999]: Failed root login',
        sourceIp: '10.0.0.5',
        protocol: 'tls',
    );

    SyslogMessageReceived::dispatch($msg);

    Event::assertDispatched(SyslogMessageReceived::class, function ($event) {
        return $event->message->message === 'Failed root login'
            && $event->message->severityName === 'Critical'
            && $event->message->protocol === 'tls';
    });
});
