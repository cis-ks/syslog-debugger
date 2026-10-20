<?php

namespace App\Services\Syslog;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class SyslogMessage
{
    /**
     * @var array<int, string>
     */
    public const array FACILITIES = [
        0 => 'kern',
        1 => 'user',
        2 => 'mail',
        3 => 'daemon',
        4 => 'auth',
        5 => 'syslog',
        6 => 'lpr',
        7 => 'news',
        8 => 'uucp',
        9 => 'cron',
        10 => 'authpriv',
        11 => 'ftp',
        12 => 'ntp',
        13 => 'security',
        14 => 'console',
        15 => 'solaris-cron',
        16 => 'local0',
        17 => 'local1',
        18 => 'local2',
        19 => 'local3',
        20 => 'local4',
        21 => 'local5',
        22 => 'local6',
        23 => 'local7',
    ];

    /**
     * @var array<int, string>
     */
    public const array SEVERITIES = [
        0 => 'Emergency',
        1 => 'Alert',
        2 => 'Critical',
        3 => 'Error',
        4 => 'Warning',
        5 => 'Notice',
        6 => 'Informational',
        7 => 'Debug',
    ];

    /**
     * @param array<string, mixed>                $attributes
     * @param array<string, array<string, mixed>> $structuredDataAttributes
     */
    public function __construct(
        public readonly int $priority,
        public readonly int $facility,
        public readonly int $severity,
        public readonly string $facilityName,
        public readonly string $severityName,
        public readonly string|null $timestamp,
        public readonly string|null $hostname,
        public readonly string|null $appName,
        public readonly string|null $processId,
        public readonly string|null $messageId,
        public readonly string|null $structuredData,
        public readonly string $message,
        public readonly string $raw,
        public readonly string|null $sourceIp = null,
        public readonly string $protocol = 'udp',
        public readonly CarbonInterface|null $receivedAt = null,
        public readonly array $attributes = [],
        public readonly array $structuredDataAttributes = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'priority' => $this->priority,
            'facility' => $this->facility,
            'severity' => $this->severity,
            'facility_name' => $this->facilityName,
            'severity_name' => $this->severityName,
            'timestamp' => $this->timestamp,
            'hostname' => $this->hostname,
            'app_name' => $this->appName,
            'process_id' => $this->processId,
            'message_id' => $this->messageId,
            'structured_data' => $this->structuredData,
            'structured_data_attributes' => $this->structuredDataAttributes,
            'message' => $this->message,
            'attributes' => $this->attributes,
            'key_value_pairs' => $this->attributes,
            'raw' => $this->raw,
            'source_ip' => $this->sourceIp,
            'protocol' => $this->protocol,
            'received_at' => ($this->receivedAt ?? Carbon::now())->toIso8601String(),
        ];
    }
}
