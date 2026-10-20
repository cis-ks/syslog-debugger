---
title: Syslog Parser & Formate
description: RFC-Standards, Framing-Erkennung, Key-Value Extraktion und SyslogMessage DTO.
order: 3
group: Syslog Daemon
---

# Syslog Parser & RFC-Spezifikationen

Der `App\Services\Syslog\SyslogParser` wandelt unstrukturierte Syslog-Streams und Datagramme in strukturierte `SyslogMessage`-Objekte um und nutzt `SyslogKeyValueParser` zur automatischen Extraktion strukturierter Attribute.

---

## 📜 Unterstützte RFC-Standards

### 1. RFC 5424 (The Syslog Protocol)
Modernes IETF-Format mit Versionsnummer, hochpräzisen ISO-8601-Zeitstempeln und strukturierten Daten (Structured Data).

```text
<PRI>VERSION TIMESTAMP HOSTNAME APP-NAME PROCID MSGID [STRUCTURED-DATA] MSG
```

**Beispiel:**
```text
<165>1 2026-10-20T12:00:00.000Z myhost.example.com myapp 1234 ID47 [exampleSDID@32473 iut="3" eventSource="App"] An application log entry
```

### 2. RFC 3164 (BSD Syslog / Legacy)
Klassisches Syslog-Format mit BSD-Datumsstempel.

```text
<PRI>Mmm dd hh:mm:ss HOSTNAME APP[PID]: MSG
```

**Beispiel:**
```text
<34>Oct 20 12:00:00 webserver nginx[5678]: GET /index.html 200
```

### 3. RFC 5425 & RFC 6587 (TLS & TCP Framing)
Für Stream-basierte Verbindungen unterstützt der Parser sowohl:
- **Octet-Counting:** `LÄNGE <PRI>...` (z. B. `123 <14>1 ...`)
- **Non-Transparent Framing:** Delimiter wie `\n` oder `\0`.

---

## 🔍 Key-Value & Attribut-Parser (`SyslogKeyValueParser`)

Der `SyslogKeyValueParser` analysiert den Nachrichtentext und extrahiert strukturierte Schlüssel-Wert-Paare in typisierte PHP-Arrays:
- **Logfmt / Key=Value Paare**: `user="admin" action=login ip=192.168.1.1 attempts=3 status=success`
- **Klammer- & Parameter-Formate**: `Database pool (active=100, idle=0, waiting=14)`
- **Key-Colon-Value Paare**: `user: admin, ip: 203.0.113.19, reason: invalid_password`
- **Embedded & Full JSON**: Automatische Extraktion und Validierung eingebetteter JSON-Objekte.
- **CEF (Common Event Format)**: Vollständige Zerlegung von `CEF:Version|Vendor|Product|Version|SignatureID|Name|Severity|Extension`.
- **RFC 5424 Structured Data**: Dekodierung von `[SDID param="value"]`-Blöcken.
- **Typen-Konvertierung**: Konvertiert Zahlen (`int`/`float`), Booleans (`true`/`false`) und `null` in native Datentypen.

---

## 📊 Priority, Facility & Severity

Der Priority-Wert (`PRI`) berechnet sich aus:
$$\text{PRI} = (\text{Facility} \times 8) + \text{Severity}$$

### Severity Levels (0 - 7)

| Level | Name | Beschreibung |
| :---: | :--- | :--- |
| **0** | `Emergency` | System ist unbrauchbar (PANIC) |
| **1** | `Alert` | Sofortiges Handeln erforderlich |
| **2** | `Critical` | Kritischer Systemzustand |
| **3** | `Error` | Fehlerzustand |
| **4** | `Warning` | Warnung |
| **5** | `Notice` | Normaler, aber bedeutsamer Zustand |
| **6** | `Informational` | Reine Informationsmeldung |
| **7** | `Debug` | Diagnosemeldung auf Debug-Ebene |

### Facility Codes (Auszug)
- `0`: Kernel Messages (`kern`)
- `1`: User-Level Messages (`user`)
- `3`: System Daemons (`daemon`)
- `4`: Security/Authorization (`auth`)
- `16 - 23`: Local Use (`local0` bis `local7`)

---

## 🧩 SyslogMessage DTO Struktur

```php
namespace App\Services\Syslog;

class SyslogMessage
{
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
    ) {}

    public function toArray(): array;
}
```
