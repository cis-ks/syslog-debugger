---
title: Testsuite & Code Quality
description: Test-Driven Development (TDD), Pest PHP, PHPStan und Laravel Pint.
order: 2
group: Allgemein
---

# Testsuite & Code Quality

Das Projekt folgt einer strengen **Test-Driven Development (TDD)**-Methodik sowie automatisierten Qualitätsprüfungen.

---

## 🧪 Testausführung mit Pest PHP

Die Testsuite umfasst Unit- und Feature-Tests für alle SAML- und Syslog-Komponenten.

```bash
# Gesamte Testsuite ausführen
php artisan test

# Nur SAML Feature-Tests ausführen
php artisan test --filter=SamlAuthTest

# Nur Syslog Unit-Tests ausführen
php artisan test --filter=SyslogParserTest
```

### Testabdeckung im Überblick

| Testdatei | Typ | Getestete Funktionalität |
| :--- | :--- | :--- |
| `tests/Feature/Auth/SamlAuthTest.php` | Feature | SP-Metadaten, Login-Redirect, ACS-Assertion & User-Provisioning, SLS-Logout |
| `tests/Unit/Saml/SamlUserTest.php` | Unit | SAML User DTO, Attribut-Handling und Fallbacks |
| `tests/Feature/Syslog/SyslogServerTest.php` | Feature | UDP 514 Socket-Handling, TLS 6514 Verbindungen, Event-Dispatching |
| `tests/Feature/Syslog/SyslogServerCommandTest.php` | Feature | Artisan CLI Optionen, Konsolenausgaben, Server-Start/Stop |
| `tests/Unit/Syslog/SyslogParserTest.php` | Unit | RFC 3164, RFC 5424, RFC 5425 Framing, PRI-Berechnung, Structured Data |
| `tests/Unit/Syslog/SyslogMessageTest.php` | Unit | Syslog Message DTO, Facility/Severity-Namen, Priority-Helper |

---

## 🔍 Statische Codeanalyse mit PHPStan / Larastan

Das Projekt erzwingt **PHPStan Level 7** zur Sicherstellung vollständiger Typensicherheit und Fehlervermeidung:

```bash
./vendor/bin/phpstan analyse
```

---

## 🎨 Code Style mit Laravel Pint

Formatierung nach PSR-12 / Laravel-Standard:

```bash
./vendor/bin/pint --format agent
```
