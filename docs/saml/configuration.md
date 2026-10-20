---
title: SAML Konfiguration
description: Umgebungsvariablen und Einstellungsoptionen für SAML 2.0.
order: 2
group: SAML Authentifizierung
---

# SAML 2.0 Konfiguration

Die SAML-Konfiguration liegt in `config/saml.php` und wird primär über Umgebungsvariablen in der `.env`-Datei gesteuert.

---

## ⚙️ Umgebungsvariablen (.env)

```env
# Allgemeine SAML-Einstellungen
SAML_STRICT=false
SAML_DEBUG=true

# Service Provider (SP)
SAML_SP_ENTITY_ID=http://localhost:8000/saml/metadata
SAML_SP_ACS_URL=http://localhost:8000/saml/acs
SAML_SP_SLS_URL=http://localhost:8000/saml/sls
SAML_SP_NAME_ID_FORMAT=urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress
SAML_SP_X509_CERT=""
SAML_SP_PRIVATE_KEY=""

# Identity Provider (IdP)
SAML_IDP_ENTITY_ID=https://idp.example.com/saml/metadata
SAML_IDP_SSO_URL=https://idp.example.com/saml/sso
SAML_IDP_SLS_URL=https://idp.example.com/saml/sls
SAML_IDP_X509_CERT="MIICdzCCAV8CAQAwDQYJKoZIhvcNAQEFBQAwMz..."

# Attribut-Mapping (IdP Assertion -> User Model)
SAML_ATTR_EMAIL=email
SAML_ATTR_NAME=name
```

---

## 📋 Konfigurationsparameter im Detail

| Schlüssel | Typ | Standard | Beschreibung |
| :--- | :--- | :--- | :--- |
| `strict` | `bool` | `false` | Bei `true` werden unverschlüsselte/unsignierte Nachrichten abgewiesen. |
| `debug` | `bool` | `env('APP_DEBUG')` | Schaltet detaillierte SAML-Protokollierung bei Fehlern ein. |
| `sp.entityId` | `string` | `{APP_URL}/saml/metadata` | Eindeutige Kennung des Service Providers. |
| `sp.assertionConsumerService.url` | `string` | `{APP_URL}/saml/acs` | Zieladresse für den POST-Empfang der IdP-Assertion. |
| `sp.singleLogoutService.url` | `string` | `{APP_URL}/saml/sls` | Zieladresse für SLO-Abmeldeanfragen. |
| `idp.entityId` | `string` | - | Entity-ID des Identity Providers. |
| `idp.singleSignOnService.url` | `string` | - | Login-URL des IdP für AuthnRequests. |
| `idp.singleLogoutService.url` | `string` | - | Logout-URL des IdP für LogoutRequests. |
| `idp.x509cert` | `string` | - | Öffentliches X.509-Zertifikat des IdP zur Signaturprüfung. |
| `attributes.email` | `string` | `email` | Name des SAML-Attributs für die E-Mail-Adresse. |
| `attributes.name` | `string` | `name` | Name des SAML-Attributs für den Benutzernamen. |
