<?php

it('shows welcome page to unauthenticated users', function () {
    $response = $this->get('/');

    $response->assertStatus(200)
        ->assertViewIs('welcome');
});
