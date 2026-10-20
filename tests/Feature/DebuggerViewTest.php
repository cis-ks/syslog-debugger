<?php

use App\Events\SyslogMessageReceived;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

uses(RefreshDatabase::class);

test('it renders the syslog debugger live dashboard successfully', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('Syslog Debugger');
    $response->assertSee('WebSocket');
    $response->assertSee('Severity');
    $response->assertSee('detail-drawer');
    $response->assertSee('drawer-kv-section');
});

test('it renders the dashboard for authenticated saml user', function () {
    $user = User::factory()->create([
        'name' => 'Max Mustermann',
        'email' => 'max@example.com',
    ]);

    $response = $this->actingAs($user)->get('/');

    $response->assertStatus(200);
    $response->assertSee('Max Mustermann');
    $response->assertSee('Abmelden');
});

test('it includes reverb and echo configuration meta tags', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('reverb-key', false);
    $response->assertSee('reverb-host', false);
    $response->assertSee('reverb-port', false);
});

test('it dispatches simulated syslog message via post endpoint', function () {
    Event::fake([SyslogMessageReceived::class]);

    $response = $this->postJson('/debugger/simulate', [
        'severity' => 3,
        'facility' => 4,
        'protocol' => 'tls',
        'message' => 'Simulated test error',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('success', true);
    $response->assertJsonPath('data.severity', 3);
    $response->assertJsonPath('data.facility', 4);
    $response->assertJsonPath('data.protocol', 'tls');
    $response->assertJsonPath('data.message', 'Simulated test error');

    Event::assertDispatched(SyslogMessageReceived::class, function ($event) {
        return $event->message->message === 'Simulated test error'
            && $event->message->severity === 3
            && $event->message->facility === 4
            && $event->message->protocol === 'tls';
    });
});

test('it redirects unauthenticated guests to saml login in production environment', function () {
    $this->app['env'] = 'production';

    $response = $this->get('/');

    $response->assertRedirect(route('saml.login'));
});

test('it blocks unauthenticated json requests with 401 in production environment', function () {
    $this->app['env'] = 'production';
    $this->withoutMiddleware(PreventRequestForgery::class);

    $response = $this->postJson('/debugger/simulate', [
        'severity' => 3,
        'facility' => 4,
        'protocol' => 'tls',
        'message' => 'Simulated test error',
    ]);

    $response->assertStatus(401);
});

test('it allows authenticated users to access dashboard in production environment', function () {
    $this->app['env'] = 'production';

    $user = User::factory()->create([
        'name' => 'Prod User',
        'email' => 'prod@example.com',
    ]);

    $response = $this->actingAs($user)->get('/');

    $response->assertStatus(200);
    $response->assertSee('Prod User');
});

test('it allows authenticated users to simulate syslog messages in production environment', function () {
    $this->app['env'] = 'production';
    $this->withoutMiddleware(PreventRequestForgery::class);
    Event::fake([SyslogMessageReceived::class]);

    $user = User::factory()->create([
        'name' => 'Prod User',
        'email' => 'prod@example.com',
    ]);

    $response = $this->actingAs($user)->postJson('/debugger/simulate', [
        'severity' => 3,
        'facility' => 4,
        'protocol' => 'tls',
        'message' => 'Simulated test error',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('success', true);
});
