---
title: Übersicht & Architektur
description: Dokumentation des Syslog Debuggers und der integrierten Funktionen.
order: 1
group: Allgemein
---

# Syslog Debugger Dokumentation

Der **Syslog Debugger** ist eine modulare Laravel-Anwendung zur Erfassung, Analyse und Protokollierung von Syslog-Mitteilungen in Echtzeit via UDP und TLS sowie zur sicheren Benutzerauthentifizierung mittels Enterprise SAML 2.0 SSO.

---

## 🚀 Hauptfunktionen

### 1. SAML 2.0 Single Sign-On & Single Logout
- Vollständige Integration basierend auf `onelogin/php-saml`.
- SP-Initiated Login Flow (`/saml/login`) mit konfigurierbarem RelayState.
- Bereitstellung von SP-Metadaten als XML (`/saml/metadata`).
- Assertion Consumer Service (`/saml/acs`) mit automatischer Benutzererstellung und Attribut-Synchronisation.
- Single Logout Service (`/saml/sls`) zur sicheren Sitzungsbeendigung.

### 2. Reaktiver Syslog-Server (Daemon)
- Asynchroner, nicht-blockierender Server basierend auf ReactPHP (`react/socket`, `react/datagram`, `react/event-loop`).
- Gleichzeitiger Empfang auf:
  - **UDP Port 514** (Standard RFC 3164 / RFC 5424 Datagramme)
  - **TLS/TCP Port 6514** (Verschlüsseltes Syslog gemäß RFC 5425)
- Entkoppeltes Event-System über das Laravel Event `SyslogMessageReceived`.

### 3. Leistungsfähiger Syslog-Parser
- Automatische Erkennung und Dekodierung von:
  - **RFC 3164** (BSD Syslog / Legacy Format)
  - **RFC 5424** (IETF Modern Syslog mit Structured Data)
  - **RFC 5425 / RFC 6587 Framing** (Octet-Counting sowie Newline/Null-Byte Delimiter)
- Fallback-Mechanismus für fehlerhafte oder unvollständige Datagramme.

### 4. WebSocket & Live Web-UI
- Echtzeitübertragung ohne Persistenz via **Laravel Reverb**.
- Moderne, schlichte Benutzeroberfläche basierend auf **Flowbite & Tailwind CSS**.
- In-Memory-Filterung nach Schweregrad (Severity), Facility, Protokoll (UDP/TLS) und Volltext ohne Datenverlust.
- Inspektor-Modal für Rohdaten und JSON-Exportfunktion.

### 5. CLI Daemon (`php artisan syslog:server`)
- Dedizierter Artisan-Befehl zur Steuerung und Überwachung des Syslog-Dienstes.
- Farbcodierte Live-Konsolenausgabe nach Severity (Notfall bis Debug).
- Flexible Konfiguration von Ports, Protokollen und SSL-Zertifikaten über CLI-Optionen.

---

## 🛠️ Technologie-Stack

| Komponente | Technologie | Version / Paket |
| :--- | :--- | :--- |
| **Framework** | Laravel | 13.x (PHP 8.4) |
| **SAML Toolkit** | OneLogin PHP-SAML | ^4.3 |
| **WebSocket Server** | Laravel Reverb | ^1.8 |
| **Frontend & UI** | Tailwind CSS / Flowbite / Echo | v4 / Flowbite 3 / Echo |
| **Event Loop & Sockets** | ReactPHP | `react/socket`, `react/datagram` |
| **Testing** | Pest PHP | ^5.0 |
| **Statische Analyse** | Larastan / PHPStan | Level 7 |
| **Code Style** | Laravel Pint | PSR-12 / Laravel Preset |
| **Dokumentation** | Laradocs | ^1.2 |

---
