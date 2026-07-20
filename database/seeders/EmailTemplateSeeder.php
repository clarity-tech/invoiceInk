<?php

namespace Database\Seeders;

use App\Enums\EmailTemplateType;
use App\Mail\Templates\EmailTemplateService;
use App\Models\Organization;

/**
 * Email templates are overrides: without a row the app falls back to
 * DefaultEmailTemplates. This seeds a couple of customised organizations so the
 * "customized" state of the template editor has something to show.
 */
class EmailTemplateSeeder extends ProductionSafeSeeder
{
    public function __construct(
        private readonly EmailTemplateService $templates,
    ) {}

    protected function seed(): void
    {
        $this->info('Seeding customized email templates...');

        $organizations = Organization::whereIn('name', ['ACME Manufacturing Corp', 'Demo Company Ltd'])->get();

        foreach ($organizations as $organization) {
            $this->templates->save(
                $organization,
                EmailTemplateType::InvoiceInitial,
                'Invoice {invoice_number} from '.$organization->name,
                "Hello,\n\nPlease find invoice {invoice_number} for {total} attached, due {due_date}.\n\nThank you for your business.\n\n".$organization->name
            );

            $this->templates->save(
                $organization,
                EmailTemplateType::InvoiceOverdue,
                'Overdue: invoice {invoice_number}',
                "Hello,\n\nOur records show invoice {invoice_number} for {total} became due on {due_date} and is still outstanding.\n\nIf payment is already on its way, please ignore this note.\n\n".$organization->name
            );
        }

        $this->info('✓ Customized templates for '.$organizations->count().' organizations');
    }
}
