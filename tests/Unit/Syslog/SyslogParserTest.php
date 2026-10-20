<?php

use App\Services\Syslog\SyslogParser;

test('it parses rfc3164 bsd syslog message correctly', function () {
    $raw = "<34>Oct 11 22:14:15 mymachine su[123]: 'su root' failed for lonvick";

    $parser = new SyslogParser();
    $message = $parser->parse($raw, '127.0.0.1', 'udp');

    expect($message->priority)->toBe(34)
        ->and($message->facility)->toBe(4) // auth
        ->and($message->severity)->toBe(2) // crit
        ->and($message->facilityName)->toBe('auth')
        ->and($message->severityName)->toBe('Critical')
        ->and($message->hostname)->toBe('mymachine')
        ->and($message->appName)->toBe('su')
        ->and($message->processId)->toBe('123')
        ->and($message->message)->toBe("'su root' failed for lonvick")
        ->and($message->sourceIp)->toBe('127.0.0.1')
        ->and($message->protocol)->toBe('udp');
});

test('it parses rfc5424 ietf syslog message correctly', function () {
    $raw = '<165>1 2003-10-11T22:14:15.003Z mymachine.example.com evntslog 47 ID47 [exampleSDID@32473 iut="3" eventSource="Application" eventID="1011"] An application event log entry';

    $parser = new SyslogParser();
    $message = $parser->parse($raw, '192.168.1.10', 'tls');

    expect($message->priority)->toBe(165)
        ->and($message->facility)->toBe(20) // local4
        ->and($message->severity)->toBe(5) // notice
        ->and($message->facilityName)->toBe('local4')
        ->and($message->severityName)->toBe('Notice')
        ->and($message->hostname)->toBe('mymachine.example.com')
        ->and($message->appName)->toBe('evntslog')
        ->and($message->processId)->toBe('47')
        ->and($message->messageId)->toBe('ID47')
        ->and($message->structuredData)->toBe('[exampleSDID@32473 iut="3" eventSource="Application" eventID="1011"]')
        ->and($message->message)->toBe('An application event log entry')
        ->and($message->sourceIp)->toBe('192.168.1.10')
        ->and($message->protocol)->toBe('tls');
});

test('it extracts rfc5425 octet-counted framed messages from stream buffer', function () {
    $msg1 = '<134>1 2026-10-20T12:00:00Z host app - - - First message';
    $msg2 = '<134>1 2026-10-20T12:00:01Z host app - - - Second message';

    $streamBuffer = strlen($msg1) . ' ' . $msg1 . strlen($msg2) . ' ' . $msg2;

    $parser = new SyslogParser();
    [$messages, $remainingBuffer] = $parser->extractFramedMessages($streamBuffer);

    expect($messages)->toHaveCount(2)
        ->and($messages[0])->toBe($msg1)
        ->and($messages[1])->toBe($msg2)
        ->and($remainingBuffer)->toBe('');
});

test('it extracts key value pairs and structured data from syslog messages during parsing', function () {
    $raw = '<165>1 2026-10-20T12:00:00Z mymachine app 123 ID47 [audit@12345 user="admin" result="success"] Connection established src=192.168.1.50 dst=10.0.0.1 proto=TCP sport=49152 dport=443';

    $parser = new SyslogParser();
    $message = $parser->parse($raw, '127.0.0.1', 'tls');

    expect($message->attributes)->toBe([
        'src' => '192.168.1.50',
        'dst' => '10.0.0.1',
        'proto' => 'TCP',
        'sport' => 49152,
        'dport' => 443,
    ]);

    expect($message->structuredDataAttributes)->toBe([
        'audit@12345' => [
            'user' => 'admin',
            'result' => 'success',
        ],
    ]);

    $array = $message->toArray();
    expect($array)->toHaveKey('attributes')
        ->and($array)->toHaveKey('key_value_pairs')
        ->and($array)->toHaveKey('structured_data_attributes')
        ->and($array['attributes'])->toBe([
            'src' => '192.168.1.50',
            'dst' => '10.0.0.1',
            'proto' => 'TCP',
            'sport' => 49152,
            'dport' => 443,
        ]);
});
