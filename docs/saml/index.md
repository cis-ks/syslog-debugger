---
title: SAML 2.0 Einführung
description: Grundlagen der SAML 2.0 Authentifizierung im Syslog Debugger.
order: 1
group: SAML Authentifizierung
slug: saml
---

# SAML 2.0 Authentifizierung

Die Authentifizierung im **Syslog Debugger** erfolgt über das standardisierte **SAML 2.0 Web Browser SSO Profil** unter Verwendung der Bibliothek `onelogin/php-saml`.

---

## 🔒 Architektur & Funktionsweise

Die Anwendung agiert als **Service Provider (SP)** und delegiert die Authentifizierung an einen zentralen **Identity Provider (IdP)** (z. B. Keycloak, Okta, Microsoft Entra ID / Azure AD, Ping Identity).

### Kernkomponenten

- **`App\Services\Saml\SamlService`**:
  Kapselt die OneLogin SAML2 Auth-Engine, übernimmt die Validierung der Signaturen, verarbeitet Responses und generiert SP-Metadaten.
- **`App\Services\Saml\SamlUser`**:
  Typensicheres Data Transfer Object (DTO) für SAML-Identitäten, welches NameID, SessionIndex und Assertion-Attribute bereitstellt.
- **`App\Http\Controllers\Auth\SamlController`**:
  HTTP-Controller für alle SAML-relevanten Endpunkte (`/saml/metadata`, `/saml/login`, `/saml/acs`, `/saml/sls`).
- **CSRF-Ausnahmen**:
  In `bootstrap/app.php` sind `/saml/acs` und `/saml/sls` von der standardmäßigen Laravel CSRF-Prüfung ausgenommen, um IdP-POST-Bindings zu ermöglichen.

---

## 🔄 Authentifizierungsablauf (SP-Initiated)

```text
[Benutzer]               [Syslog Debugger (SP)]               [Identity Provider (IdP)]
    |                              |                                     |
    |---- 1. GET /saml/login ----->|                                     |
    |                              |---- 2. Redirect mit SAMLRequest --->|
    |                              |                                     |
    |                              |<--- 3. User authentifiziert sich ---|
    |                              |                                     |
    |<--- 4. POST /saml/acs (SAMLResponse) ------------------------------|
    |                              |
    |                              |-- 5. Validierung & User Login (Auth::login)
    |<--- 6. Redirect zu Ziel-URL -|
```

1. Der Anwender ruft `/saml/login` auf.
2. `SamlService` erzeugt eine signierte/komprimierte AuthnRequest und leitet zum IdP weiter.
3. Der Anwender authentifiziert sich beim IdP.
4. Der IdP sendet ein HTTP-POST mit der `SAMLResponse` an die ACS-URL (`/saml/acs`).
5. Der Controller validiert die Assertion, erzeugt bzw. aktualisiert den Benutzer in der Datenbank und loggt ihn in Laravel ein (`Auth::login`).
6. Der Anwender wird auf die ursprüngliche Seite oder das Dashboard weitergeleitet.
