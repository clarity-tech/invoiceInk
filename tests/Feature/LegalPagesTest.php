<?php

it('serves each legal document under its own title, linking to the other', function (string $path, string $title, string $otherPath) {
    $this->get($path)
        ->assertOk()
        ->assertSee("<title>{$title} - ".config('app.name').'</title>', false)
        ->assertSee("<h1>{$title}</h1>", false)
        ->assertSee('href="'.url($otherPath).'"', false);
})->with([
    'terms' => ['/terms-of-service', 'Terms of Service', '/privacy-policy'],
    'privacy' => ['/privacy-policy', 'Privacy Policy', '/terms-of-service'],
]);
