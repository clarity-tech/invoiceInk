<?php

namespace App\Mail\Templates;

use App\Enums\EmailTemplateType;

/**
 * Code-defined default email templates.
 *
 * These are the "latest version" defaults. Organizations that haven't
 * customized a template always get these. Updating this file instantly
 * updates all non-customized orgs — no migration needed.
 *
 * None of these bodies link to the document. The public link is owned by the
 * "View {type} Online" CTA button that emails.custom-document renders directly
 * beneath the body, gated on `app.invoice_email.include_view_online_button` and
 * on the per-send toggle. A link here would render a second, identical CTA that
 * the toggle cannot switch off. `{{view_url}}` stays available for orgs that
 * want the link inline in a customized template.
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
                    .'<p>Thank you for your business!</p>',
            ],

            EmailTemplateType::InvoiceReminder => [
                'subject' => 'Reminder: Invoice #{{invoice_number}} is due {{due_date}}',
                'body' => '<p>Dear {{customer_name}},</p>'
                    .'<p>This is a friendly reminder that invoice <strong>#{{invoice_number}}</strong> '
                    .'for <strong>{{balance_due}}</strong> is due on <strong>{{due_date}}</strong>.</p>'
                    .'<p>If you have already made the payment, please disregard this message.</p>'
                    .'<p>Thank you!</p>',
            ],

            EmailTemplateType::InvoiceOverdue => [
                'subject' => 'Overdue: Invoice #{{invoice_number}} — {{days_overdue}} days past due',
                'body' => '<p>Dear {{customer_name}},</p>'
                    .'<p>Invoice <strong>#{{invoice_number}}</strong> for <strong>{{balance_due}}</strong> '
                    .'was due on <strong>{{due_date}}</strong> and is now <strong>{{days_overdue}} days overdue</strong>.</p>'
                    .'<p>Please arrange payment at your earliest convenience.</p>'
                    .'<p>If you have any questions, please don\'t hesitate to reach out.</p>',
            ],

            EmailTemplateType::InvoiceThankYou => [
                'subject' => 'Thank you for your payment — Invoice #{{invoice_number}}',
                'body' => '<p>Dear {{customer_name}},</p>'
                    .'<p>We have received your payment for invoice <strong>#{{invoice_number}}</strong>.</p>'
                    .'<p><strong>Amount Paid: {{amount_due}}</strong></p>'
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
                    .'<p>Please let us know if you have any questions.</p>',
            ],

            EmailTemplateType::EstimateReminder => [
                'subject' => 'Reminder: Estimate #{{invoice_number}} expires {{due_date}}',
                'body' => '<p>Dear {{customer_name}},</p>'
                    .'<p>This is a reminder that estimate <strong>#{{invoice_number}}</strong> '
                    .'for <strong>{{amount_due}}</strong> is valid until <strong>{{due_date}}</strong>.</p>'
                    .'<p>Please let us know if you would like to proceed.</p>',
            ],

            EmailTemplateType::EstimateExpired => [
                'subject' => 'Estimate #{{invoice_number}} has expired',
                'body' => '<p>Dear {{customer_name}},</p>'
                    .'<p>Estimate <strong>#{{invoice_number}}</strong> for <strong>{{amount_due}}</strong> '
                    .'expired on <strong>{{due_date}}</strong>.</p>'
                    .'<p>If you are still interested, we would be happy to prepare an updated estimate for you.</p>',
            ],

            EmailTemplateType::EstimateThankYou => [
                'subject' => 'Thank you — Estimate #{{invoice_number}} accepted',
                'body' => '<p>Dear {{customer_name}},</p>'
                    .'<p>Thank you for accepting estimate <strong>#{{invoice_number}}</strong>.</p>'
                    .'<p>We will follow up shortly with the next steps.</p>'
                    .'<p>We look forward to working with you!</p>',
            ],

            // The list of invoices and their totals is added below the body when
            // sending, from the invoices actually selected.
            EmailTemplateType::CustomerReminder => [
                'subject' => 'Payment reminder: {{invoice_numbers}}',
                'body' => '<p>Dear {{customer_name}},</p>'
                    .'<p>This is a friendly reminder that the invoices below are still awaiting payment.</p>'
                    .'<p>If you have already made the payment, please disregard this message.</p>'
                    .'<p>Thank you!</p>',
            ],
        };
    }

    /**
     * Get the template variable names a template can use, for documentation/UI.
     *
     * @return array<string, string>
     */
    public static function availableVariables(?EmailTemplateType $type = null): array
    {
        if ($type === EmailTemplateType::CustomerReminder) {
            return [
                '{{customer_name}}' => 'Customer name',
                '{{invoice_numbers}}' => 'Numbers of the invoices in the reminder',
                '{{organization_name}}' => 'Your organization name',
                '{{organization_email}}' => 'Your organization email',
            ];
        }

        return [
            '{{customer_name}}' => 'Customer name',
            '{{invoice_number}}' => 'Document number',
            '{{amount_due}}' => 'Total amount (formatted with currency)',
            '{{balance_due}}' => 'Amount still owed after payments',
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
