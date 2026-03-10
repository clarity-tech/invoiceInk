<?php

namespace App\Mail\Templates;

use App\Enums\EmailTemplateType;

/**
 * Code-defined default email templates.
 *
 * These are the "latest version" defaults. Organizations that haven't
 * customized a template always get these. Updating this file instantly
 * updates all non-customized orgs — no migration needed.
 */
class DefaultEmailTemplates
{
    /**
     * @return array{subject: string, body: string}
     */
    public static function get(EmailTemplateType $type): array
    {
        return match ($type) {
            EmailTemplateType::InvoiceInitial => [
                'subject' => 'Invoice #{{invoice_number}} from {{organization_name}}',
                'body' => '<p>Dear {{customer_name}},</p>'
                    .'<p>Thank you for your business. Please find your invoice details below.</p>'
                    .'<p><strong>Invoice #{{invoice_number}}</strong></p>'
                    .'<ul>'
                    .'<li>Issue Date: {{issue_date}}</li>'
                    .'<li>Due Date: {{due_date}}</li>'
                    .'<li>Amount Due: {{amount_due}}</li>'
                    .'</ul>'
                    .'<p>You can view, print, and download your invoice using the link below:</p>'
                    .'<p><a href="{{view_url}}">View Invoice Online</a></p>'
                    .'<p>Thank you for your business!</p>',
            ],

            EmailTemplateType::InvoiceReminder => [
                'subject' => 'Reminder: Invoice #{{invoice_number}} is due {{due_date}}',
                'body' => '<p>Dear {{customer_name}},</p>'
                    .'<p>This is a friendly reminder that invoice <strong>#{{invoice_number}}</strong> '
                    .'for <strong>{{amount_due}}</strong> is due on <strong>{{due_date}}</strong>.</p>'
                    .'<p>If you have already made the payment, please disregard this message.</p>'
                    .'<p><a href="{{view_url}}">View Invoice Online</a></p>'
                    .'<p>Thank you!</p>',
            ],

            EmailTemplateType::InvoiceOverdue => [
                'subject' => 'Overdue: Invoice #{{invoice_number}} — {{days_overdue}} days past due',
                'body' => '<p>Dear {{customer_name}},</p>'
                    .'<p>Invoice <strong>#{{invoice_number}}</strong> for <strong>{{amount_due}}</strong> '
                    .'was due on <strong>{{due_date}}</strong> and is now <strong>{{days_overdue}} days overdue</strong>.</p>'
                    .'<p>Please arrange payment at your earliest convenience.</p>'
                    .'<p><a href="{{view_url}}">View Invoice Online</a></p>'
                    .'<p>If you have any questions, please don\'t hesitate to reach out.</p>',
            ],

            EmailTemplateType::InvoiceThankYou => [
                'subject' => 'Thank you for your payment — Invoice #{{invoice_number}}',
                'body' => '<p>Dear {{customer_name}},</p>'
                    .'<p>We have received your payment for invoice <strong>#{{invoice_number}}</strong>.</p>'
                    .'<p><strong>Amount Paid: {{amount_due}}</strong></p>'
                    .'<p>You can view your invoice receipt here:</p>'
                    .'<p><a href="{{view_url}}">View Invoice Online</a></p>'
                    .'<p>Thank you for your business. We appreciate your prompt payment!</p>',
            ],

            EmailTemplateType::EstimateInitial => [
                'subject' => 'Estimate #{{invoice_number}} from {{organization_name}}',
                'body' => '<p>Dear {{customer_name}},</p>'
                    .'<p>Thank you for considering our services. Please find your estimate details below.</p>'
                    .'<p><strong>Estimate #{{invoice_number}}</strong></p>'
                    .'<ul>'
                    .'<li>Issue Date: {{issue_date}}</li>'
                    .'<li>Valid Until: {{due_date}}</li>'
                    .'<li>Total: {{amount_due}}</li>'
                    .'</ul>'
                    .'<p>You can view the full estimate using the link below:</p>'
                    .'<p><a href="{{view_url}}">View Estimate Online</a></p>'
                    .'<p>Please let us know if you have any questions.</p>',
            ],

            EmailTemplateType::EstimateReminder => [
                'subject' => 'Reminder: Estimate #{{invoice_number}} expires {{due_date}}',
                'body' => '<p>Dear {{customer_name}},</p>'
                    .'<p>This is a reminder that estimate <strong>#{{invoice_number}}</strong> '
                    .'for <strong>{{amount_due}}</strong> is valid until <strong>{{due_date}}</strong>.</p>'
                    .'<p>Please let us know if you would like to proceed.</p>'
                    .'<p><a href="{{view_url}}">View Estimate Online</a></p>',
            ],

            EmailTemplateType::EstimateExpired => [
                'subject' => 'Estimate #{{invoice_number}} has expired',
                'body' => '<p>Dear {{customer_name}},</p>'
                    .'<p>Estimate <strong>#{{invoice_number}}</strong> for <strong>{{amount_due}}</strong> '
                    .'expired on <strong>{{due_date}}</strong>.</p>'
                    .'<p>If you are still interested, we would be happy to prepare an updated estimate for you.</p>'
                    .'<p><a href="{{view_url}}">View Original Estimate</a></p>',
            ],

            EmailTemplateType::EstimateThankYou => [
                'subject' => 'Thank you — Estimate #{{invoice_number}} accepted',
                'body' => '<p>Dear {{customer_name}},</p>'
                    .'<p>Thank you for accepting estimate <strong>#{{invoice_number}}</strong>.</p>'
                    .'<p>We will follow up shortly with the next steps.</p>'
                    .'<p><a href="{{view_url}}">View Estimate Online</a></p>'
                    .'<p>We look forward to working with you!</p>',
            ],
        };
    }

    /**
     * Get all available template variable names for documentation/UI.
     *
     * @return array<string, string>
     */
    public static function availableVariables(): array
    {
        return [
            '{{customer_name}}' => 'Customer name',
            '{{invoice_number}}' => 'Document number',
            '{{amount_due}}' => 'Total amount (formatted with currency)',
            '{{subtotal}}' => 'Subtotal before tax',
            '{{tax_amount}}' => 'Tax amount',
            '{{currency}}' => 'Currency code (e.g. INR, USD)',
            '{{due_date}}' => 'Due date / Valid until date',
            '{{issue_date}}' => 'Issue date',
            '{{organization_name}}' => 'Your organization name',
            '{{organization_email}}' => 'Your organization email',
            '{{view_url}}' => 'Public link to view the document',
            '{{days_overdue}}' => 'Days past due (for overdue templates)',
            '{{status}}' => 'Document status',
        ];
    }
}
