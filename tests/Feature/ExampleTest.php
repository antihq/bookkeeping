<?php

use function Pest\Laravel\get;

it('redirects to dashboard', function () {
    $response = get('/');

    $response->assertRedirect('/dashboard');
});
