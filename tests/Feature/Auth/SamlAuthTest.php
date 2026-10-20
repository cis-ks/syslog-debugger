<?php

use App\Models\User;
use App\Services\Saml\SamlService;
use App\Services\Saml\SamlUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

test('it renders sp metadata xml', function () {
    $response = $this->get(route('saml.metadata'));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
    expect($response->getContent())->toContain('EntityDescriptor');
});

test('it redirects to idp sso url on login', function () {
    $response = $this->get(route('saml.login'));

    $response->assertStatus(302);
    $location = $response->headers->get('Location');
    expect($location)->toContain('idp.example.com/saml/sso')
        ->and($location)->toContain('SAMLRequest=');
});

test('it authenticates user and creates account on valid acs response', function () {
    $samlUser = new SamlUser(
        nameId: 'saml.user@example.com',
        attributes: [
            'email' => ['saml.user@example.com'],
            'name' => ['Jane Doe'],
        ],
        sessionIndex: 'test-session-123',
    );

    $mockSamlService = Mockery::mock(SamlService::class);
    $mockSamlService->shouldReceive('processAcs')->once()->andReturn($samlUser);

    $this->app->instance(SamlService::class, $mockSamlService);

    $response = $this->post(route('saml.acs'), [
        'SAMLResponse' => base64_encode('<xml>dummy</xml>'),
    ]);

    $response->assertRedirect('/');
    expect(Auth::check())->toBeTrue()
        ->and(Auth::user()->email)->toBe('saml.user@example.com')
        ->and(Auth::user()->name)->toBe('Jane Doe');

    $this->assertDatabaseHas('users', [
        'email' => 'saml.user@example.com',
        'name' => 'Jane Doe',
    ]);
});

test('it logs out user on single logout service request', function () {
    $user = User::factory()->create([
        'email' => 'logout.user@example.com',
    ]);

    $this->actingAs($user);
    expect(Auth::check())->toBeTrue();

    $mockSamlService = Mockery::mock(SamlService::class);
    $mockSamlService->shouldReceive('processSls')->once()->andReturn('http://localhost');

    $this->app->instance(SamlService::class, $mockSamlService);

    $response = $this->get(route('saml.sls'));

    $response->assertRedirect('http://localhost');
    expect(Auth::check())->toBeFalse();
});
