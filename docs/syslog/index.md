---
title: Syslog Daemon Einführung
description: Überblick über den asynchronen Syslog-Empfangsdienst.
order: 1
group: Syslog Daemon
slug: syslog
---

# Syslog Daemon & Architektur

Der **Syslog Daemon** ist als reaktiver, nicht-blockierender Server konzipiert, der zeitgleich unverschlüsselte UDP-Mitteilungen auf Port 514 und TLS-verschlüsselte TCP-Mitteilungen auf Port 6514 entgegennimmt.

---

## ⚡ Reaktives Design mit ReactPHP

Anstelle traditioneller, blockierender PHP Sockets setzt die Anwendung auf **ReactPHP Event-Loops**:

```text
       +-----------------------+
       |   ReactPHP Loop       |
       +-----------+-----------+
                   |
         +---------+---------+
         |                   |
         v                   v
   +-----------+       +-----------+
   |  UDP 514  |       | TLS 6514  |
   | Datagram  |       | TcpSocket |
   +-----+-----+       +-----+-----+
         |                   |
         +---------+---------+
                   |
                   v
         +-------------------+
         |   SyslogParser    |
         +---------+---------+
                   |
                   v
   +-------------------------------+
   | Event: SyslogMessageReceived  |
   +-------------------------------+
```

### Vorteile dieses Ansatzes:
1. **Hohe Durchsatzleistung:** Hunderte Verbindungen und Datagramme werden in einem einzigen PHP-Prozess ohne Threading-Overhead verarbeitet.
2. **Entkopplung:** Eingehende Pakete triggern standardisierte Laravel Events (`App\Events\SyslogMessageReceived`).
3. **Erweiterbarkeit:** Weitere Handler (z. B. WebSocket-Broadcasts, Datenbank-Persistierung, Queue-Dispatching) können über Event-Listener nahtlos angebunden werden.

---

## 📦 Komponentenübersicht

- **`App\Services\Syslog\SyslogServer`**:
  Verwaltet Socket-Instanzen, Event-Loop-Zustand, TLS-Konfiguration und Graceful Shutdown.
- **`App\Services\Syslog\SyslogParser`**:
  Reine, zustandslose Parsing-Engine zur Zerlegung roher Payloads in strukturierte DTOs.
- **`App\Services\Syslog\SyslogMessage`**:
  Immutable Data Transfer Object für geparste Syslog-Meldungen.
- **`App\Events\SyslogMessageReceived`**:
  Laravel Event, das bei jedem verarbeiteten Syslog-Paket ausgelöst wird.
- **`App\Console\Commands\SyslogServerCommand`**:
  Artisan CLI Command zur Steuerung des Servers.
