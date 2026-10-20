---
title: Syslog Server
description: Server-Implementierung, Sockets und Event-Verarbeitung.
order: 2
group: Syslog Daemon
---

# Syslog Server & Events

Die Klasse `App\Services\Syslog\SyslogServer` bildet das Herzstück des Syslog-Empfangsdienstes.

---

## 🔌 Socket-Verwaltung

### UDP Server (Port 514)
- Verwendet `React\Datagram\Factory` zur Erstellung eines UDP-Sockets (`0.0.0.0:514`).
- Verarbeitet eingehende Datagramme atomar.
- Unterstützt Mehrfachnachrichten pro Paket (z. B. getrennt durch Newlines).

### TLS Server (Port 6514)
- Basiert auf `React\Socket\SocketServer` mit SSL-Stream-Kontext:
  ```php
  $socket = new SocketServer("tls://0.0.0.0:6514", [
      'tls' => [
          'local_cert' => $certPath,
          'local_pk'   => $keyPath,
          'verify_peer' => false,
      ]
  ], $loop);
  ```
- Unterstützt Streaming-Pufferung und TCP-Framing (RFC 5425 / RFC 6587).
- Erkennt Verbindungsabbrüche und schließt Streams sauber.

---

## 📢 Event-System: `SyslogMessageReceived`

Jede erfolgreich geparste Nachricht wird als Event im Laravel Event-Dispatcher publiziert:

```php
namespace App\Events;

use App\Services\Syslog\SyslogMessage;
use Illuminate\Foundation\Events\Dispatchable;

class SyslogMessageReceived
{
    use Dispatchable;

    public function __construct(
        public readonly SyslogMessage $message,
    ) {}
}
```

### Eigenen Listener registrieren

```php
use App\Events\SyslogMessageReceived;
use Illuminate\Support\Facades\Event;

Event::listen(SyslogMessageReceived::class, function (SyslogMessageReceived $event) {
    $msg = $event->message;
    
    // Beispiel: Logik für Alarmierung bei Emergency/Alert
    if ($msg->severity <= 1) {
        // z. B. Slack-Notification oder Logeintrag
    }
});
```

---

## 🛡️ Programmatische Verwendung

```php
use App\Services\Syslog\SyslogServer;

$server = app(SyslogServer::class);

// Eigener Callback-Handler (optional neben Events)
$server->onMessage(function ($message) {
    echo "Empfangen von {$message->clientIp}: {$message->message}\n";
});

// Server auf Standardports starten
$server->start(
    udpPort: 514,
    tlsPort: 6514,
    tlsCertPath: storage_path('certs/server.crt'),
    tlsKeyPath: storage_path('certs/server.key'),
);
```
