# 📡 Syslog Debugger

Ein hochperformantes, modulares Echtzeit-Werkzeug auf Basis von **Laravel (PHP 8.4)** zur Erfassung, Analyse, Strukturierung und Fehlerdiagnose von Syslog-Nachrichten über **UDP (Port 514)** und verschlüsseltes **TLS (Port 6514)**.

Die Anwendung kombiniert einen asynchronen **ReactPHP**-Daemon mit **Laravel Reverb** WebSockets, einem fehlertoleranten **Key-Value & JSON Parser**, **SAML 2.0 SSO** und einem modernen, reaktiven Dashboard auf Basis von **Flowbite & Tailwind CSS**.

---

## 🚀 Features

### 1. Asynchroner Syslog-Daemon (ReactPHP)
- **Multi-Protokoll-Empfang**:
  - **UDP Port 514**: Klassische RFC 3164 (BSD) & RFC 5424 (IETF) Syslog-Datagramme.
  - **TLS Port 6514**: Verschlüsselte Syslog-Verbindungen gemäß RFC 5425 mit konfigurierbarem SSL-Zertifikat und Key.
- **Nicht-blockierender Event-Loop**: Hoher Durchsatz bei minimalem Ressourcenverbrauch.
- **Entkoppeltes Event-System**: Eingehende Nachrichten werden über `SyslogMessageReceived` in das Laravel-Event- und Broadcast-System eingespeist.
- **CLI-Steuerung**: Dedicated Artisan-Befehl `php artisan syslog:server` mit farbkodierter Live-Konsolenausgabe.

### 2. Multi-Format Parser & Key/Value-Extraktor
- **Syslog-Standards**:
  - **RFC 3164** (BSD Syslog / Legacy)
  - **RFC 5424** (IETF Modern Syslog mit Structured Data)
  - **RFC 5425 / RFC 6587 Framing** (Octet-Counting sowie Newline- und Null-Byte-Delimiting)
- **Automatischer Key-Value & Attribut-Parser**:
  - Extrahiert strukturierte Attribute aus **Logfmt** (`key=value`, `key="value with spaces"`), **CEF** (Common Event Format), **JSON-Payloads**, **Key-Colon** (`user: admin, ip: ...`) und **RFC 5424 Structured Data**.
  - Automatische Typkonvertierung von Zahlen, Booleans und Null-Werten.

### 3. Echtzeit-WebSockets & In-Memory Dashboard
- **Laravel Reverb**: Broadcastet Nachrichten sofort ohne Datenbank-Persistierung über den WebSocket-Kanal `syslog`.
- **Verlustfreie Filterung**: Clientseitige In-Memory-Verwaltung in `debugger.js` garantiert, dass beim dynamischen Anpassen und Zurücksetzen von Filtern keine empfangenen Nachrichten verloren gehen.
- **Reaktives Web-Dashboard**:
  - Entwickelt mit **Tailwind CSS** und **Flowbite**.
  - Dynamische Filter nach Schweregrad (Severity 0–7), Facility, Protokoll (UDP/TLS) und Volltextsuche mit Treffer-Hervorhebung.
  - Statistiken (Gesamt, Gefiltert, Fehler, UDP, TLS), Pause/Resume-Modus, Puffer-Reset (abgesichert via **SweetAlert2**) und JSON-Export.
  - **Offcanvas-Detail-Drawer**: Blickdichtes Seiten-Panel zur Inspektion von Metadaten, extrahierten Key-Value-Paaren, Raw-Payloads und JSON-Objekten mit 1-Klick-Kopierfunktion.

### 4. Enterprise SAML 2.0 Authentifizierung
- Basiert auf `onelogin/php-saml`.
- SP-Initiated Login (`/saml/login`), Metadaten-Bereitstellung (`/saml/metadata`), Assertion Consumer Service (`/saml/acs`) und Single Logout (`/saml/sls`).
- **Produktionssicherheit**: Die Middleware `RequireAuthInProduction` erzwingt in Produktionsumgebungen (`APP_ENV=production`) die verbindliche Anmeldung für das Dashboard, während lokale Entwicklungs- und Testumgebungen flexibel zugänglich bleiben.

### 5. Integrierte Dokumentation (Laradocs)
- Vollständig gepflegte, modulare Dokumentation direkt im Projekt (`docs/`), abrufbar unter `/docs`.

---

## 🛠️ Technologie-Stack

| Bereich | Technologie / Paket |
| :--- | :--- |
| **Framework** | Laravel 12 (PHP 8.4) |
| **Event-Loop & Sockets** | ReactPHP (`react/socket`, `react/datagram`, `react/event-loop`) |
| **WebSockets** | Laravel Reverb (`laravel/reverb`), Laravel Echo, Pusher-JS |
| **SAML 2.0 SSO** | OneLogin PHP-SAML (`onelogin/php-saml`) |
| **Frontend & UI** | Tailwind CSS v4, Flowbite 3, SweetAlert2, Vite |
| **Dokumentation** | Laradocs (`petebishwhip/laradocs`) |
| **Testing & QA** | Pest PHP v5, PHPStan (Level 7), Laravel Pint |

---

## 📋 Voraussetzungen

- **PHP 8.4** oder neuer (inkl. Erweiterungen: `openssl`, `mbstring`, `sockets`, `pdo`)
- **Composer** 2.x
- **Node.js** (LTS) & **npm**

---

## 📦 Installation & Setup

1. **Repository klonen und Abhängigkeiten installieren:**
   ```bash
   git clone <repository-url>
   cd syslog-debugger

   composer install
   npm install
   ```

2. **Umgebungskonfiguration einrichten:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Reverb & Broadcasting initialisieren:**
   In der `.env` sicherstellen, dass Broadcasting aktiviert ist:
   ```dotenv
   BROADCAST_CONNECTION=reverb
   
   REVERB_APP_ID=syslog-debugger
   REVERB_APP_KEY=syslog-debugger-key
   REVERB_APP_SECRET=syslog-debugger-secret
   REVERB_HOST="localhost"
   REVERB_PORT=8080
   REVERB_SCHEME=http

   VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
   VITE_REVERB_HOST="${REVERB_HOST}"
   VITE_REVERB_PORT="${REVERB_PORT}"
   VITE_REVERB_SCHEME="${REVERB_SCHEME}"
   ```

4. **SAML 2.0 konfigurieren (`.env`):**
   ```dotenv
   SAML_SP_ENTITY_ID="http://localhost:8000/saml/metadata"
   SAML_IDP_ENTITY_ID="https://your-idp.example.com/metadata"
   SAML_IDP_SSO_URL="https://your-idp.example.com/sso"
   SAML_IDP_SLO_URL="https://your-idp.example.com/slo"
   SAML_IDP_X509CERT="MIIB..."
   ```

5. **Frontend-Assets kompilieren:**
   ```bash
   npm run build
   # oder für die Entwicklung:
   npm run dev
   ```

---

## 🚦 Dienste starten

Für den vollständigen Betrieb werden folgende Prozesse ausgeführt:

1. **Syslog-Daemon starten (UDP 514 & TLS 6514):**
   ```bash
   php artisan syslog:server
   ```
   *Optionen:*
   - `--udp-port=514`: UDP-Port anpassen
   - `--tls-port=6514`: TLS-Port anpassen
   - `--no-udp` / `--no-tls`: Einzelne Protokolle deaktivieren
   - `--cert=path/to/cert.pem` / `--key=path/to/key.pem`: Eigene TLS-Zertifikate angeben

2. **WebSocket-Server (Laravel Reverb) starten:**
   ```bash
   php artisan reverb:start
   ```

3. **Web-Server starten:**
   ```bash
   php artisan serve
   ```

4. **Dashboard im Browser aufrufen:**
   - Web-UI: `http://localhost:8000/`
   - Laradocs-Dokumentation: `http://localhost:8000/docs`
   - SAML-Metadaten: `http://localhost:8000/saml/metadata`

---

## 🧪 Tests & Qualitätssicherung

Das Projekt folgt einer strikten **Test-Driven Development (TDD)** Methodik:

```bash
# Pest Testsuite ausführen (38 Tests, 100% grün)
php artisan test

# Statische Codeanalyse mit PHPStan auf Level 7
vendor/bin/phpstan analyse

# Code-Formatierung prüfen / anwenden mit Laravel Pint
vendor/bin/pint --format agent
```

---

## 📖 Laradocs-Befehle

```bash
# Dokumentations-Suchindex neu generieren
php artisan laradocs:cache

# Dokumentations-Cache leeren
php artisan laradocs:clear
```

---

## 📄 Lizenz

Dieses Projekt ist unter der [MIT-Lizenz](LICENSE) lizenziert.
