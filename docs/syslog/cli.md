---
title: Artisan CLI Server
description: Verwendung und Parameter des `syslog:server` Konsolenbefehls.
order: 4
group: Syslog Daemon
---

# Artisan CLI Server Command

Der Syslog-Server kann bequem über den Artisan-Befehl `syslog:server` gestartet werden.

---

## 💻 Verwendung

```bash
php artisan syslog:server [Optionen]
```

### Typische Aufrufe

**1. Standardstart (UDP 514 & TLS 6514):**
```bash
php artisan syslog:server
```

**2. Angepasste Ports und Zertifikate:**
```bash
php artisan syslog:server --udp-port=1514 --tls-port=6514 --tls-cert=/etc/ssl/syslog.crt --tls-key=/etc/ssl/syslog.key
```

**3. Nur UDP aktivieren:**
```bash
php artisan syslog:server --no-tls --udp-port=514
```

**4. Nur TLS aktivieren:**
```bash
php artisan syslog:server --no-udp --tls-port=6514 --tls-cert=/path/to/cert.pem --tls-key=/path/to/key.pem
```

---

## ⚙️ Verfügbare Optionen

| Option | Standard | Beschreibung |
| :--- | :--- | :--- |
| `--udp-port=PORT` | `514` | Port für unverschlüsselte UDP-Mitteilungen. |
| `--tls-port=PORT` | `6514` | Port für TLS-verschlüsselte TCP-Verbindungen. |
| `--tls-cert=PFAD` | `null` | Pfad zur X.509-Zertifikatsdatei (PEM). |
| `--tls-key=PFAD` | `null` | Pfad zum privaten RSA/EC-Schlüssel (PEM). |
| `--no-udp` | `false` | Deaktiviert den UDP-Listener vollständig. |
| `--no-tls` | `false` | Deaktiviert den TLS-Listener vollständig. |

---

## 🎨 Konsolenausgabe

Eingehende Mitteilungen werden in Echtzeit formatiert und entsprechend ihres Schweregrads farblich hervorgehoben:

- **Rot / Error:** `Emergency (0)`, `Alert (1)`, `Critical (2)`, `Error (3)`
- **Gelb / Warning:** `Warning (4)`
- **Grün / Info:** `Notice (5)`, `Informational (6)`
- **Grau / Muted:** `Debug (7)`

**Formatierung:**
```text
[2026-10-20 12:00:00] [local0.warning] [192.168.1.50:514] auth-service[1042]: Failed login attempt for admin
```
