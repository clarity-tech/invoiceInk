<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;

#[Signature('app:setup {--seed : Run database seeders after migration} {--fresh : Drop all tables before migrating}')]
#[Description('Set up the application for local development (migrate, storage, build)')]
class AppSetup extends Command
{
    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('This command is for local development only. Aborting.');

            return self::FAILURE;
        }

        if (! app()->environment('local', 'testing') && ! confirm('You are not in local environment. Continue?', false)) {
            return self::FAILURE;
        }

        $this->info('Setting up InvoiceInk for local development...');
        $this->newLine();

        // 1. Environment check
        if (! file_exists(base_path('.env'))) {
            $this->task('Copying .env.example to .env', function () {
                copy(base_path('.env.example'), base_path('.env'));
            });

            $this->call('key:generate');
        }

        // 2. Database
        if ($this->option('fresh')) {
            $this->task('Migrating database (fresh)', fn () => $this->callSilently('migrate:fresh', ['--force' => true]) === 0);
        } else {
            $this->task('Running migrations', fn () => $this->callSilently('migrate', ['--force' => true]) === 0);
        }

        // 3. Seed if requested
        if ($this->option('seed')) {
            $this->task('Seeding database', fn () => $this->callSilently('db:seed', ['--force' => true]) === 0);
        }

        // 4. Storage
        $this->task('Creating storage link', fn () => $this->callSilently('storage:link', ['--force' => true]) === 0);

        $this->task('Setting up S3 bucket', fn () => $this->callSilently('app:setup-storage') === 0);

        // 5. Caches
        $this->task('Clearing caches', function () {
            $this->callSilently('config:clear');
            $this->callSilently('cache:clear');
            $this->callSilently('view:clear');
        });

        $this->newLine();
        $this->info('Setup complete! Run `sail bun run dev` to start the Vite dev server.');

        return self::SUCCESS;
    }
}
