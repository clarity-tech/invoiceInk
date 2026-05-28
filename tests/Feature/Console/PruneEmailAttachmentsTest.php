<?php

use Illuminate\Support\Facades\Storage;

test('prune command deletes files older than the cutoff and keeps recent files', function () {
    $disk = config('filesystems.default');
    Storage::fake($disk);

    Storage::disk($disk)->put('email-attachments/old/file.pdf', 'old');
    Storage::disk($disk)->put('email-attachments/new/file.pdf', 'new');

    // Backdate the "old" file 72 hours ago.
    touch(
        Storage::disk($disk)->path('email-attachments/old/file.pdf'),
        now()->subHours(72)->getTimestamp(),
    );

    $this->artisan('email-attachments:prune', ['--hours' => 48])
        ->assertExitCode(0);

    Storage::disk($disk)->assertMissing('email-attachments/old/file.pdf');
    Storage::disk($disk)->assertExists('email-attachments/new/file.pdf');
});
