<?php

namespace App\Http\Controllers;

use App\Events\SyslogMessageReceived;
use App\Services\Syslog\SyslogKeyValueParser;
use App\Services\Syslog\SyslogMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DebuggerController extends Controller
{
    /**
     * Render the main syslog debugger dashboard.
     */
    public function index(): View
    {
        return view('debugger', [
            'reverb' => [
                'key' => (string) config('reverb.apps.apps.0.key', ''),
                'host' => (string) config('reverb.apps.apps.0.options.host', 'localhost'),
                'port' => (int) config('reverb.apps.apps.0.options.port', 8080),
                'scheme' => (string) config('reverb.apps.apps.0.options.scheme', 'http'),
            ],
        ]);
    }

    /**
     * Broadcast a simulated syslog message for testing and demonstration.
     */
    public function simulate(Request $request): JsonResponse
    {
        $severity = (int) $request->input('severity', random_int(0, 7));
        $facility = (int) $request->input('facility', random_int(0, 23));
        $protocol = (string) $request->input('protocol', random_int(0, 1) === 1 ? 'tls' : 'udp');

        $severityNames = [
            0 => 'Emergency',
            1 => 'Alert',
            2 => 'Critical',
            3 => 'Error',
            4 => 'Warning',
            5 => 'Notice',
            6 => 'Informational',
            7 => 'Debug',
        ];

        $facilityNames = [
            0 => 'kern',
            1 => 'user',
            2 => 'mail',
            3 => 'daemon',
            4 => 'auth',
            5 => 'syslog',
            16 => 'local0',
            17 => 'local1',
            18 => 'local2',
            19 => 'local3',
            20 => 'local4',
            21 => 'local5',
            22 => 'local6',
            23 => 'local7',
        ];

        $sampleMessages = [
            'User admin successfully authenticated via SAML SSO user="admin@example.com" auth_method=SAML2 session_id="saml_98af3"',
            'Connection timed out after 30000ms while reaching upstream service peer="10.0.4.12" port=8443 retries=3',
            'TLS handshake failed for peer 192.168.10.45: certificate has expired src=192.168.10.45 dst=10.0.0.1 proto=TLS',
            'Database connection pool saturated (active=100, idle=0, waiting=14, timeout=30s)',
            'Disk usage exceeded threshold on volume /var/log volume="/var/log" used_percent=94 threshold=90',
            'Worker process 4829 spawned for queue "high-priority" worker_id=4829 queue="high-priority" memory_mb=64.5',
            'Rate limit exceeded for client IP 203.0.113.19: client_ip=203.0.113.19 rate=120 limit=100 action=throttle',
            'Firewall packet dropped src=192.168.1.105 dst=10.0.0.50 proto=TCP sport=49152 dport=22 action=DROP',
            '{"event":"user_login","user_id":42,"username":"admin","role":"administrator","ip":"192.168.1.20"}',
            'System reboot initiated by user root from console user=root source=console',
        ];

        $severityName = $severityNames[$severity] ?? 'Informational';
        $facilityName = $facilityNames[$facility] ?? 'local' . ($facility % 8);
        $messageText = (string) $request->input('message', $sampleMessages[array_rand($sampleMessages)]);
        $hostname = (string) $request->input('hostname', 'srv-' . strtolower($facilityName) . '.internal');
        $appName = (string) $request->input('app_name', 'service-' . random_int(1, 8));
        $processId = (string) random_int(1000, 9999);
        $priority = ($facility * 8) + $severity;
        $structuredData = '[simulation@12345 generatedBy="WebUI" randomId="' . random_int(100, 999) . '"]';

        $kvParser = new SyslogKeyValueParser();
        $attributes = $kvParser->extract($messageText);
        $sdAttributes = $kvParser->extractFromStructuredData($structuredData);

        $msg = new SyslogMessage(
            priority: $priority,
            facility: $facility,
            severity: $severity,
            facilityName: $facilityName,
            severityName: $severityName,
            timestamp: now()->toIso8601String(),
            hostname: $hostname,
            appName: $appName,
            processId: $processId,
            messageId: 'SIM-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)),
            structuredData: $structuredData,
            message: $messageText,
            raw: sprintf('<%d>1 %s %s %s %s - %s %s', $priority, now()->toIso8601String(), $hostname, $appName, $processId, $structuredData, $messageText),
            sourceIp: '127.0.0.1',
            protocol: $protocol,
            attributes: $attributes,
            structuredDataAttributes: $sdAttributes,
        );

        SyslogMessageReceived::dispatch($msg);

        return response()->json([
            'success' => true,
            'message' => 'Simulated syslog message dispatched to WebSocket',
            'data' => $msg->toArray(),
        ]);
    }
}
