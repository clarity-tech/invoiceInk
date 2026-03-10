<?php

namespace App\Enums;

enum EmailTemplateType: string
{
    case InvoiceInitial = 'invoice_initial';
    case InvoiceReminder = 'invoice_reminder';
    case InvoiceOverdue = 'invoice_overdue';
    case InvoiceThankYou = 'invoice_thank_you';
    case EstimateInitial = 'estimate_initial';
    case EstimateReminder = 'estimate_reminder';
    case EstimateExpired = 'estimate_expired';
    case EstimateThankYou = 'estimate_thank_you';

    public function label(): string
    {
        return match ($this) {
            self::InvoiceInitial => 'Initial Invoice',
            self::InvoiceReminder => 'Invoice Reminder',
            self::InvoiceOverdue => 'Overdue Notice',
            self::InvoiceThankYou => 'Payment Thank You',
            self::EstimateInitial => 'Initial Estimate',
            self::EstimateReminder => 'Estimate Reminder',
            self::EstimateExpired => 'Estimate Expired',
            self::EstimateThankYou => 'Estimate Accepted',
        };
    }

    public function documentType(): string
    {
        return match ($this) {
            self::InvoiceInitial, self::InvoiceReminder, self::InvoiceOverdue, self::InvoiceThankYou => 'invoice',
            self::EstimateInitial, self::EstimateReminder, self::EstimateExpired, self::EstimateThankYou => 'estimate',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::InvoiceInitial => 'Sent when sharing an invoice for the first time',
            self::InvoiceReminder => 'Gentle reminder before the due date',
            self::InvoiceOverdue => 'Sent when payment is past due',
            self::InvoiceThankYou => 'Sent after payment is received',
            self::EstimateInitial => 'Sent when sharing an estimate for the first time',
            self::EstimateReminder => 'Reminder before the estimate expires',
            self::EstimateExpired => 'Sent when the estimate validity has passed',
            self::EstimateThankYou => 'Sent when the estimate is accepted',
        };
    }

    /**
     * @return array<self>
     */
    public static function forDocumentType(string $type): array
    {
        return array_filter(self::cases(), fn (self $case) => $case->documentType() === $type);
    }
}
