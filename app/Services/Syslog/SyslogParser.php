<?php

namespace App\Services\Syslog;

use Carbon\Carbon;

class SyslogParser
{
    protected SyslogKeyValueParser $keyValueParser;

    public function __construct(SyslogKeyValueParser|null $keyValueParser = null)
    {
        $this->keyValueParser = $keyValueParser ?? new SyslogKeyValueParser();
    }
    /**
     * @return array{0: array<int, string>, 1: string}
     */
    public function extractFramedMessages(string $buffer): array
    {
        $messages = [];
        $trimmedBuffer = ltrim($buffer, "\r\n");

        if ($trimmedBuffer === '') {
            return [[], ''];
        }

        // Check if stream uses RFC 5425 / RFC 6587 octet counting framing: "<length> <msg>"
        if (preg_match('/^(\d+)\s/', $trimmedBuffer, $matches) === 1) {
            $offset = 0;
            $len = strlen($trimmedBuffer);

            while ($offset < $len && preg_match('/^(\d+)\s/', substr($trimmedBuffer, $offset), $m) === 1) {
                $msgLen = (int) $m[1];
                $prefixLen = strlen($m[0]);
                $msgStart = $offset + $prefixLen;

                if ($msgStart + $msgLen <= $len) {
                    $messages[] = substr($trimmedBuffer, $msgStart, $msgLen);
                    $offset = $msgStart + $msgLen;
                } else {
                    // Message is still incomplete in buffer
                    break;
                }
            }

            $remaining = substr($trimmedBuffer, $offset);

            return [$messages, $remaining];
        }

        // Fallback: Newline-delimited framing
        $lines = explode("\n", $buffer);
        $remaining = (string) array_pop($lines);

        foreach ($lines as $line) {
            $line = trim($line, "\r");
            if ($line !== '') {
                $messages[] = $line;
            }
        }

        return [$messages, $remaining];
    }

    public function parse(string $raw, string|null $sourceIp = null, string $protocol = 'udp'): SyslogMessage
    {
        $raw = trim($raw);

        // Try RFC 5424 parsing
        if (preg_match('/^<(\d{1,3})>1\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\[.*?\]|-)(?:\s+(.*))?$/s', $raw, $matches) === 1) {
            $priority = (int) $matches[1];
            $facility = intdiv($priority, 8);
            $severity = $priority % 8;
            $timestamp = $matches[2] !== '-' ? $matches[2] : null;
            $hostname = $matches[3] !== '-' ? $matches[3] : null;
            $appName = $matches[4] !== '-' ? $matches[4] : null;
            $processId = $matches[5] !== '-' ? $matches[5] : null;
            $messageId = $matches[6] !== '-' ? $matches[6] : null;
            $structuredData = $matches[7] !== '-' ? $matches[7] : null;
            $message = $matches[8] ?? '';

            // Strip UTF-8 BOM if present
            if (str_starts_with($message, "\xEF\xBB\xBF")) {
                $message = substr($message, 3);
            }

            $attributes = $this->keyValueParser->extract($message);
            $structuredDataAttributes = $structuredData !== null
                ? $this->keyValueParser->extractFromStructuredData($structuredData)
                : [];

            return new SyslogMessage(
                priority: $priority,
                facility: $facility,
                severity: $severity,
                facilityName: SyslogMessage::FACILITIES[$facility] ?? 'unknown',
                severityName: SyslogMessage::SEVERITIES[$severity] ?? 'unknown',
                timestamp: $timestamp,
                hostname: $hostname,
                appName: $appName,
                processId: $processId,
                messageId: $messageId,
                structuredData: $structuredData,
                message: $message,
                raw: $raw,
                sourceIp: $sourceIp,
                protocol: $protocol,
                receivedAt: Carbon::now(),
                attributes: $attributes,
                structuredDataAttributes: $structuredDataAttributes,
            );
        }

        // Try RFC 3164 parsing: <PRI>TIMESTAMP HOSTNAME TAG[PID]: MSG
        if (preg_match('/^<(\d{1,3})>([A-Za-z]{3}\s+\d{1,2}\s+\d{2}:\d{2}:\d{2})?\s*([^\s:]+)?\s+([a-zA-Z0-9_\-\/\.]+)(?:\[(\d+)\])?:\s*(.*)$/s', $raw, $matches) === 1) {
            $priority = (int) $matches[1];
            $facility = intdiv($priority, 8);
            $severity = $priority % 8;
            $timestamp = !empty($matches[2]) ? $matches[2] : null;
            $hostname = !empty($matches[3]) ? $matches[3] : null;
            $appName = !empty($matches[4]) ? $matches[4] : null;
            $processId = !empty($matches[5]) ? $matches[5] : null;
            $message = $matches[6];

            $attributes = $this->keyValueParser->extract($message);

            return new SyslogMessage(
                priority: $priority,
                facility: $facility,
                severity: $severity,
                facilityName: SyslogMessage::FACILITIES[$facility] ?? 'unknown',
                severityName: SyslogMessage::SEVERITIES[$severity] ?? 'unknown',
                timestamp: $timestamp,
                hostname: $hostname,
                appName: $appName,
                processId: $processId,
                messageId: null,
                structuredData: null,
                message: $message,
                raw: $raw,
                sourceIp: $sourceIp,
                protocol: $protocol,
                receivedAt: Carbon::now(),
                attributes: $attributes,
            );
        }

        // Fallback generic parsing
        $priority = 13; // default user.notice (13 = 1*8 + 5)
        $message = $raw;

        if (preg_match('/^<(\d{1,3})>(.*)$/s', $raw, $matches) === 1) {
            $priority = (int) $matches[1];
            $message = $matches[2];
        }

        $facility = intdiv($priority, 8);
        $severity = $priority % 8;

        $attributes = $this->keyValueParser->extract($message);

        return new SyslogMessage(
            priority: $priority,
            facility: $facility,
            severity: $severity,
            facilityName: SyslogMessage::FACILITIES[$facility] ?? 'unknown',
            severityName: SyslogMessage::SEVERITIES[$severity] ?? 'unknown',
            timestamp: null,
            hostname: null,
            appName: null,
            processId: null,
            messageId: null,
            structuredData: null,
            message: $message,
            raw: $raw,
            sourceIp: $sourceIp,
            protocol: $protocol,
            receivedAt: Carbon::now(),
            attributes: $attributes,
        );
    }
}
