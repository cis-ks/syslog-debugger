---
title: WebSocket & Live Web-UI
description: Reverb WebSocket Broadcasting, In-Memory Pufferung und Web-UI mit Flowbite & Tailwind CSS.
order: 5
group: Syslog Daemon
---

# WebSocket & Live Web-UI

Der Syslog Debugger stellt eine moderne, reaktive Benutzeroberfläche bereit, die eingehende Syslog-Nachrichten über **Laravel Reverb** in Echtzeit empfängt und clientseitig analysiert.

---

## ⚡ Architektur & Nicht-Persistenz

Gemäß den Anforderungen werden empfangene Syslog-Meldungen **nicht in einer relationalen Datenbank persistiert**. Stattdessen werden sie direkt über WebSockets im Stream übertragen:

```
[Syslog UDP/TLS Socket] 
        ↓
[SyslogServer / Parser] 
        ↓
[SyslogMessageReceived Event (ShouldBroadcastNow)] 
        ↓
[Laravel Reverb WebSocket Server (:8080)]
        ↓
[Laravel Echo / Browser Client In-Memory Buffer]
```

---

## 🖥️ Web-UI Funktionen (Flowbite & Tailwind CSS)

1. **In-Memory Pufferung (Kein Datenverlust beim Filtern)**:
   - Alle im Browser empfangenen Nachrichten verbleiben im JavaScript-Array `allMessages`.
   - Bei Änderung von Filtern (Volltextsuche, Severity, Facility, Protokoll) wird die Ansicht rein reaktiv gefiltert.
   - Zurücksetzen des Filters stellt sofort alle zuvor empfangenen Nachrichten wieder dar.

2. **Echtzeit-Statistiken**:
   - Gesamtzahl empfangener Pakete im RAM.
   - Anzahl sichtbarer (gefilterter) Meldungen.
   - Aufschlüsselung nach Fehlern (Level 0-3), UDP Port 514 und TLS Port 6514.

3. **Steuerung & Werkzeuge**:
   - **Stream Pausieren / Fortsetzen**: Hält das Scrolling an, puffert neue Pakete im Hintergrund.
   - **Puffer leeren**: Bereinigt den lokalen Browserspeicher nach Bestätigung via **SweetAlert2**-Dialog.
   - **JSON Export**: Exportiert die gefilterte oder vollständige Liste als `.json`-Datei.
   - **Simulation**: Generiert Test-Syslog-Pakete per Knopfdruck zur Überprüfung des Live-Streams.

4. **Sicherheit & Authentifizierung**:
   - In Produktivumgebungen (`APP_ENV=production`) ist der Zugriff auf die Maske und Simulationsendpunkte zwingend durch SAML 2.0 Authentifizierung geschützt (`RequireAuthInProduction`-Middleware). Unauthentifizierte Anfragen werden automatisch zum SAML-Login weitergeleitet.

5. **Detail-Inspektor (Seitenleiste / Offcanvas-Drawer)**:
   - Modernes, aufklappbares Seiten-Panel zur Ansicht der unformatierten Rohdaten (Raw Payload) sowie der geparsten JSON-Struktur.
   - **Key/Value-Inspektor**: Automatische Erkennung und farblich typisierte Darstellung extrahierter Schlüssel-Wert-Paare aus dem Meldungstext.
   - Beim Klick auf eine beliebige andere Zeile in der Tabelle wird der Inhalt im geöffneten Seiten-Panel sofort reaktiv aktualisiert, ohne die Leiste schließen zu müssen.
   - 1-Klick-Kopieren für Rohdaten und JSON in die Zwischenablage sowie Tastatur-Bedienung (`Esc` zum Schließen).

---

## 🚀 Reverb WebSocket Server starten

Um den WebSocket-Dienst lokal zu starten:

```bash
php artisan reverb:start
```

Der Syslog-Server wird parallel gestartet:

```bash
php artisan syslog:server
```
