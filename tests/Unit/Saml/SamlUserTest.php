<?php

use App\Services\Saml\SamlUser;

test('it creates a saml user dto with attributes and getters', function () {
    $samlUser = new SamlUser(
        nameId: 'user@example.com',
        attributes: [
            'email' => ['user@example.com'],
            'name' => ['John Doe'],
            'roles' => ['admin', 'analyst'],
        ],
        sessionIndex: 'session-xyz-123',
        nameIdFormat: 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
    );

    expect($samlUser->getNameId())->toBe('user@example.com')
        ->and($samlUser->getEmail())->toBe('user@example.com')
        ->and($samlUser->getName())->toBe('John Doe')
        ->and($samlUser->getSessionIndex())->toBe('session-xyz-123')
        ->and($samlUser->getAttribute('roles'))->toBe(['admin', 'analyst'])
        ->and($samlUser->getFirstAttribute('roles'))->toBe('admin');
});

test('it falls back to nameId when email attribute is missing', function () {
    $samlUser = new SamlUser(
        nameId: 'fallback@example.com',
        attributes: [],
    );

    expect($samlUser->getEmail())->toBe('fallback@example.com')
        ->and($samlUser->getName())->toBe('fallback@example.com');
});
