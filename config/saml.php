<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SAML Strict Mode
    |--------------------------------------------------------------------------
    |
    | When strict mode is enabled, the SAML toolkit will reject unsigned or
    | unencrypted messages if it expects them to be signed or encrypted.
    |
    */

    'strict' => env('SAML_STRICT', false),

    /*
    |--------------------------------------------------------------------------
    | SAML Debug Mode
    |--------------------------------------------------------------------------
    |
    | Enables internal debugging info for SAML requests/responses.
    |
    */

    'debug' => env('SAML_DEBUG', env('APP_DEBUG', false)),

    /*
    |--------------------------------------------------------------------------
    | Service Provider (SP) Settings
    |--------------------------------------------------------------------------
    |
    | Defines the entity ID and endpoints of this Laravel application.
    |
    */

    'sp' => [
        'entityId' => env('SAML_SP_ENTITY_ID', env('APP_URL', 'http://localhost') . '/saml/metadata'),
        'assertionConsumerService' => [
            'url' => env('SAML_SP_ACS_URL', env('APP_URL', 'http://localhost') . '/saml/acs'),
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
        ],
        'singleLogoutService' => [
            'url' => env('SAML_SP_SLS_URL', env('APP_URL', 'http://localhost') . '/saml/sls'),
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        ],
        'NameIDFormat' => env('SAML_SP_NAME_ID_FORMAT', 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress'),
        'x509cert' => env('SAML_SP_X509_CERT', ''),
        'privateKey' => env('SAML_SP_PRIVATE_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Identity Provider (IdP) Settings
    |--------------------------------------------------------------------------
    |
    | Details of the external Identity Provider that authenticates users.
    |
    */

    'idp' => [
        'entityId' => env('SAML_IDP_ENTITY_ID', 'https://idp.example.com/saml/metadata'),
        'singleSignOnService' => [
            'url' => env('SAML_IDP_SSO_URL', 'https://idp.example.com/saml/sso'),
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        ],
        'singleLogoutService' => [
            'url' => env('SAML_IDP_SLS_URL', 'https://idp.example.com/saml/sls'),
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        ],
        'x509cert' => env('SAML_IDP_X509_CERT', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security options controlling signing and encryption behavior.
    |
    */

    'security' => [
        'nameIdEncrypted' => false,
        'authnRequestsSigned' => false,
        'logoutRequestSigned' => false,
        'logoutResponseSigned' => false,
        'signMetadata' => false,
        'wantMessagesSigned' => false,
        'wantAssertionsSigned' => false,
        'wantNameId' => true,
        'wantNameIdEncrypted' => false,
        'requestedAuthnContext' => true,
        'relaxDestinationValidation' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Attribute Mapping
    |--------------------------------------------------------------------------
    |
    | Map SAML attributes returned by IdP to local user fields.
    |
    */

    'attributes' => [
        'email' => env('SAML_ATTR_EMAIL', 'email'),
        'name' => env('SAML_ATTR_NAME', 'name'),
    ],

];
