---
title: SAML Endpunkte
description: HTTP-Routen und Controller-Abläufe für SAML 2.0.
order: 3
group: SAML Authentifizierung
---

# SAML 2.0 Endpunkte & Routen

Die SAML-Routen sind in `routes/web.php` unter dem Prefix `/saml` mit dem Route-Namen-Prefix `saml.` registriert.

---

## 🌐 Routenübersicht

| Route Name | Methode | Pfad | Controller-Methode | Beschreibung |
| :--- | :--- | :--- | :--- | :--- |
| `saml.metadata` | `GET` | `/saml/metadata` | `SamlController@metadata` | Liefert das SP-Metadaten XML-Dokument. |
| `saml.login` | `GET` | `/saml/login` | `SamlController@login` | Initiert den Login und leitet zum IdP weiter. |
| `saml.acs` | `POST` | `/saml/acs` | `SamlController@acs` | Assertion Consumer Service (SAMLResponse Empfang). |
| `saml.sls` | `GET`, `POST` | `/saml/sls` | `SamlController@sls` | Single Logout Service (LogoutResponse / LogoutRequest). |

---

## 🔍 Detailbeschreibung der Endpunkte

### 1. `GET /saml/metadata`
- **Antwort:** `Content-Type: text/xml; charset=UTF-8`
- **Zweck:** Dient dem Identity Provider zum automatischen Einlesen der SP-Zertifikate, EntityID und Bindings.
- **Fehlerbehandlung:** Wirft eine `RuntimeException`, falls die generierten Metadaten ungültig sind.

### 2. `GET /saml/login`
- **Parameter:** `returnTo` (optional, z. B. `/saml/login?returnTo=/dashboard`)
- **Ablauf:** Übergibt die Zieladresse als `RelayState` und leitet den Browser mit HTTP-302 an den IdP weiter.

### 3. `POST /saml/acs`
- **Ablauf:**
  1. Validiert die eingehende `SAMLResponse`.
  2. Extrahiert NameID sowie konfigurierte Attribute (E-Mail, Name).
  3. Führt ein `User::firstOrCreate()` auf Basis der E-Mail-Adresse durch.
  4. Aktualisiert den Namen bei Änderungen.
  5. Meldet den Benutzer über `Auth::login($user)` an.
  6. Leitet zum im `RelayState` hinterlegten Ziel oder zur Startseite weiter.

### 4. `GET|POST /saml/sls`
- **Ablauf:**
  1. Meldet den Benutzer lokal über `Auth::logout()` ab.
  2. Invalidiert die aktuelle Laravel-Session und regeneriert das CSRF-Token.
  3. Verarbeitet den SAML Single-Logout-Flow.
  4. Leitet zur vom IdP zurückgegebenen URL weiter.
