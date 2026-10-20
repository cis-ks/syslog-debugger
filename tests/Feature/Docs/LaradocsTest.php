<?php

test('it renders laradocs home page successfully', function () {
    $response = $this->get('/docs');

    $response->assertStatus(200);
    expect($response->getContent())->toContain('Übersicht');
});

test('it renders saml documentation page successfully', function () {
    $response = $this->get('/docs/saml');

    $response->assertStatus(200);
    expect($response->getContent())->toContain('SAML 2.0 Authentifizierung');
});

test('it renders syslog documentation page successfully', function () {
    $response = $this->get('/docs/syslog');

    $response->assertStatus(200);
    expect($response->getContent())->toContain('Syslog Daemon');
});
