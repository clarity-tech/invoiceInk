<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

abstract class ProductionSafeSeeder extends Seeder
{
    /**
     * Run the database seeds only in local environment.
     */
    public function run(): void
    {
        if (! $this->isLocalEnvironment()) {
            $this->command->error('Seeders can only be run in the local environment for safety.');
            $this->command->error('Current environment: ' . app()->environment());
            $this->command->error('To run seeders, set APP_ENV=local in your .env file.');
            
            return;
        }

        $this->command->info('Running seeder: ' . static::class);
        $this->seed();
    }

    /**
     * Check if we're in a local environment.
     */
    protected function isLocalEnvironment(): bool
    {
        return app()->environment('local');
    }

    /**
     * The actual seeding logic that subclasses should implement.
     */
    abstract protected function seed(): void;

    /**
     * Helper method to display seeding progress.
     */
    protected function info(string $message): void
    {
        $this->command->info($message);
    }

    /**
     * Helper method to display warnings.
     */
    protected function warn(string $message): void
    {
        $this->command->warn($message);
    }
}