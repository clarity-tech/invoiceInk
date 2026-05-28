<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('email-attachments:prune {--hours=48 : Delete files older than this many hours}')]
#[Description('Delete temp email attachments older than the given age')]
class PruneEmailAttachments extends Command
{
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours)->getTimestamp();

        $disk = Storage::disk(config('filesystems.default'));
        $deleted = 0;

        foreach ($disk->allFiles('email-attachments') as $path) {
            if ($disk->lastModified($path) < $cutoff) {
                $disk->delete($path);
                $deleted++;
            }
        }

        $this->info("Pruned {$deleted} expired email attachment(s).");

        return self::SUCCESS;
    }
}
