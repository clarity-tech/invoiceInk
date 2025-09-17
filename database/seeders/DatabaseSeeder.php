<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with comprehensive demo data.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command->error('Seeders can only be run in the local environment for safety.');
            $this->command->error('Current environment: '.app()->environment());
            $this->command->error('To run seeders, set APP_ENV=local in your .env file.');

            return;
        }

        $this->command->info('Starting comprehensive database seeding...');
        $this->command->info('This will create demo data for the multitenant invoicing application.');

        if (! $this->confirmSeeding()) {
            $this->command->warn('Seeding cancelled by user.');

            return;
        }

        $startTime = microtime(true);

        // Run seeders in the correct order to maintain referential integrity
        $this->command->info('Step 1/4: Creating users and organizations...');
        $this->call(UserSeeder::class);

        $this->command->info('Step 2/4: Creating tax templates...');
        $this->call(TaxTemplateSeeder::class);

        $this->command->info('Step 3/4: Creating customers with locations...');
        $this->call(CustomerSeeder::class);

        $this->command->info('Step 4/4: Creating invoices and estimates...');
        $this->call(InvoiceSeeder::class);

        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);

        $this->displaySeedingSummary($executionTime);
    }

    /**
     * Ask user for confirmation before seeding.
     */
    private function confirmSeeding(): bool
    {
        if (app()->environment('local')) {
            $this->command->warn('You are about to seed the database with demo data.');
            $this->command->warn('This will create multiple users, teams, companies, customers, and invoices.');

            return $this->command->confirm('Do you want to continue?', true);
        }

        return true;
    }

    /**
     * Display a summary of what was created.
     */
    private function displaySeedingSummary(float $executionTime): void
    {
        $this->command->info('');
        $this->command->info('🎉 Database seeding completed successfully!');
        $this->command->info("⏱️  Execution time: {$executionTime} seconds");
        $this->command->info('');

        $this->command->info('📊 Demo Data Summary:');
        $this->command->info('   👥 Users & Teams: Multiple business organizations created');
        $this->command->info('   🏢 Companies: 7 companies across different currencies (USD, EUR, GBP, INR)');
        $this->command->info('   👨‍💼 Customers: 30+ customers with realistic business data');
        $this->command->info('   📄 Invoices: 40+ invoices and estimates with various statuses');
        $this->command->info('');

        $this->command->info('🔑 Demo Login Credentials:');
        $this->command->info('   Admin: admin@invoicing.claritytech.io (password: password)');
        $this->command->info('   Demo User: demo@invoicing.claritytech.io (password: password)');
        $this->command->info('   Business Users: john@acmecorp.com, sarah@techstartup.com, maria@euroconsult.de');
        $this->command->info('   All passwords: password');
        $this->command->info('');

        $this->command->info('🌐 Demo Scenarios Available:');
        $this->command->info('   🏭 Manufacturing (ACME Corp) - USD with complex B2B invoicing');
        $this->command->info('   💻 Tech Startup (TechStart) - USD with service-based billing');
        $this->command->info('   🏛️ European Consulting (EuroConsult) - EUR with VAT');
        $this->command->info('   🇮🇳 Indian Company (Demo Company) - INR with GST');
        $this->command->info('   🌍 Global Corporation - Multi-currency, multi-team setup');
        $this->command->info('');

        $this->command->info('✨ Features Demonstrated:');
        $this->command->info('   • Multitenant team isolation');
        $this->command->info('   • Multiple currencies (USD, EUR, GBP, INR)');
        $this->command->info('   • Various invoice statuses (draft, sent, paid, overdue)');
        $this->command->info('   • Estimates and invoices');
        $this->command->info('   • Team member management and invitations');
        $this->command->info('   • Custom domains and team URLs');
        $this->command->info('   • Complex business relationships');
        $this->command->info('');

        $this->command->info('🚀 Ready to demo! Visit your application and login with any of the demo accounts.');
    }
}
