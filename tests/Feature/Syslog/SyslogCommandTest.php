<?php

test('syslog server command is registered and outputs configuration', function () {
    $this->artisan('syslog:server', ['--help' => true])
        ->assertSuccessful();
});
