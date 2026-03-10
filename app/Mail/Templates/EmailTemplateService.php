<?php

namespace App\Mail\Templates;

use App\Enums\EmailTemplateType;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\Organization;

class EmailTemplateService
{
    /**
     * Resolve a template for an organization.
     * Returns DB override if exists, otherwise code default.
     *
     * @return array{subject: string, body: string, is_customized: bool}
     */
    public function resolve(Organization $organization, EmailTemplateType $type): array
    {
        $override = EmailTemplate::where('organization_id', $organization->id)
            ->where('template_type', $type)
            ->first();

        if ($override) {
            return [
                'subject' => $override->subject,
                'body' => $override->body,
                'is_customized' => true,
            ];
        }

        $default = DefaultEmailTemplates::get($type);

        return [
            'subject' => $default['subject'],
            'body' => $default['body'],
            'is_customized' => false,
        ];
    }

    /**
     * Resolve and render a template with invoice data substituted.
     *
     * @return array{subject: string, body: string, is_customized: bool}
     */
    public function render(Invoice $invoice, EmailTemplateType $type): array
    {
        $invoice->loadMissing('organization');
        $template = $this->resolve($invoice->organization, $type);
        $variables = TemplateVariableResolver::resolve($invoice);

        return [
            'subject' => TemplateVariableResolver::render($template['subject'], $variables),
            'body' => TemplateVariableResolver::render($template['body'], $variables),
            'is_customized' => $template['is_customized'],
        ];
    }

    /**
     * Save an org-specific template override.
     */
    public function save(Organization $organization, EmailTemplateType $type, string $subject, string $body): EmailTemplate
    {
        return EmailTemplate::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'template_type' => $type,
            ],
            [
                'subject' => $subject,
                'body' => $body,
            ]
        );
    }

    /**
     * Reset to default by deleting the override.
     */
    public function resetToDefault(Organization $organization, EmailTemplateType $type): void
    {
        EmailTemplate::where('organization_id', $organization->id)
            ->where('template_type', $type)
            ->delete();
    }

    /**
     * Get all template types with their override status for an org.
     *
     * @return array<int, array{type: EmailTemplateType, label: string, description: string, document_type: string, is_customized: bool}>
     */
    public function listForOrganization(Organization $organization): array
    {
        $overrides = EmailTemplate::where('organization_id', $organization->id)
            ->pluck('template_type')
            ->map(fn ($t) => $t->value)
            ->toArray();

        return array_map(fn (EmailTemplateType $type) => [
            'type' => $type->value,
            'label' => $type->label(),
            'description' => $type->description(),
            'document_type' => $type->documentType(),
            'is_customized' => in_array($type->value, $overrides),
        ], EmailTemplateType::cases());
    }
}
