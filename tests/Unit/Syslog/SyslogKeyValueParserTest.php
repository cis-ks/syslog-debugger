<?php

use App\Services\Syslog\SyslogKeyValueParser;

test('it parses simple logfmt key value pairs', function () {
    $parser = new SyslogKeyValueParser();
    $text = 'user="john doe" action=login status=success attempts=3 duration_ms=12.5 active=true';

    $result = $parser->extract($text);

    expect($result)->toBe([
        'user' => 'john doe',
        'action' => 'login',
        'status' => 'success',
        'attempts' => 3,
        'duration_ms' => 12.5,
        'active' => true,
    ]);
});

test('it parses key value pairs with single quotes and escaped quotes', function () {
    $parser = new SyslogKeyValueParser();
    $text = "msg='hello world' path='/var/log/syslog' tag='auth-service'";

    $result = $parser->extract($text);

    expect($result)->toBe([
        'msg' => 'hello world',
        'path' => '/var/log/syslog',
        'tag' => 'auth-service',
    ]);
});

test('it parses network and firewall syslog key value pairs', function () {
    $parser = new SyslogKeyValueParser();
    $text = 'src=192.168.1.100 dst=10.0.0.1 proto=TCP sport=54321 dport=443 in=eth0 out=eth1 bytes=1048576';

    $result = $parser->extract($text);

    expect($result)->toBe([
        'src' => '192.168.1.100',
        'dst' => '10.0.0.1',
        'proto' => 'TCP',
        'sport' => 54321,
        'dport' => 443,
        'in' => 'eth0',
        'out' => 'eth1',
        'bytes' => 1048576,
    ]);
});

test('it parses comma separated and parenthesized key value pairs', function () {
    $parser = new SyslogKeyValueParser();
    $text = 'Database connection pool saturated (active=100, idle=0, waiting=14, timeout=30s)';

    $result = $parser->extract($text);

    expect($result)->toBe([
        'active' => 100,
        'idle' => 0,
        'waiting' => 14,
        'timeout' => '30s',
    ]);
});

test('it parses key colon value pairs', function () {
    $parser = new SyslogKeyValueParser();
    $text = 'Failed login attempt: user: admin, ip: 203.0.113.19, reason: invalid_password';

    $result = $parser->extract($text);

    expect($result)->toBe([
        'user' => 'admin',
        'ip' => '203.0.113.19',
        'reason' => 'invalid_password',
    ]);
});

test('it parses embedded json within message', function () {
    $parser = new SyslogKeyValueParser();
    $text = 'API request processed: {"endpoint":"/api/v1/auth","method":"POST","status":200,"user_id":42,"meta":{"ip":"127.0.0.1"}}';

    $result = $parser->extract($text);

    expect($result)->toBe([
        'endpoint' => '/api/v1/auth',
        'method' => 'POST',
        'status' => 200,
        'user_id' => 42,
        'meta' => [
            'ip' => '127.0.0.1',
        ],
    ]);
});

test('it parses entirely json messages', function () {
    $parser = new SyslogKeyValueParser();
    $text = '{"event":"user_logout","session_id":"abc-123","duration":120}';

    $result = $parser->extract($text);

    expect($result)->toBe([
        'event' => 'user_logout',
        'session_id' => 'abc-123',
        'duration' => 120,
    ]);
});

test('it parses cef common event format logs', function () {
    $parser = new SyslogKeyValueParser();
    $text = 'CEF:0|Security Corp|Threat Sensor|1.2.3|1001|Port Scan Detected|7|src=10.0.0.5 dst=192.168.1.1 spt=4000 dpt=80 msg="Multiple connection attempts"';

    $result = $parser->extract($text);

    expect($result)->toHaveKey('cef_version', 0)
        ->and($result)->toHaveKey('cef_vendor', 'Security Corp')
        ->and($result)->toHaveKey('cef_product', 'Threat Sensor')
        ->and($result)->toHaveKey('cef_product_version', '1.2.3')
        ->and($result)->toHaveKey('cef_signature_id', '1001')
        ->and($result)->toHaveKey('cef_name', 'Port Scan Detected')
        ->and($result)->toHaveKey('cef_severity', 7)
        ->and($result)->toHaveKey('src', '10.0.0.5')
        ->and($result)->toHaveKey('dst', '192.168.1.1')
        ->and($result)->toHaveKey('spt', 4000)
        ->and($result)->toHaveKey('dpt', 80)
        ->and($result)->toHaveKey('msg', 'Multiple connection attempts');
});

test('it parses rfc5424 structured data string', function () {
    $parser = new SyslogKeyValueParser();
    $structuredData = '[exampleSDID@32473 iut="3" eventSource="Application" eventID="1011"][origin ip="192.168.1.1"]';

    $result = $parser->extractFromStructuredData($structuredData);

    expect($result)->toBe([
        'exampleSDID@32473' => [
            'iut' => 3,
            'eventSource' => 'Application',
            'eventID' => 1011,
        ],
        'origin' => [
            'ip' => '192.168.1.1',
        ],
    ]);
});

test('it returns empty array when no key value pairs are found in plain message', function () {
    $parser = new SyslogKeyValueParser();
    $text = 'System reboot initiated by user root from console';

    $result = $parser->extract($text);

    expect($result)->toBe([]);
});
