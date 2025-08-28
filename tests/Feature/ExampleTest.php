<?php

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/');

    $response->assertStatus(302)
        ->assertRedirect('/login');
});
