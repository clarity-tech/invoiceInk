<?php

namespace App\Models;

use App\Enums\EmailDeliveryStatus;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The record of one attempt to email a document.
 *
 * Created as `queued` by the controller, advanced by SendDocumentEmail on the
 * worker. `failed` is only written once the queue has exhausted its retries, so
 * a transient blip does not surface to the customer as a failure.
 */
class EmailDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'invoice_id',
        'mailable_type',
        'mode',
        'status',
        'recipients',
        'error',
        'attempts',
        'queued_at',
        'sent_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => EmailDeliveryStatus::class,
            'recipients' => 'array',
            'attempts' => 'integer',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function markSending(int $attempt): void
    {
        $this->update([
            'status' => EmailDeliveryStatus::SENDING,
            'attempts' => $attempt,
        ]);
    }

    public function markSent(): void
    {
        $this->update([
            'status' => EmailDeliveryStatus::SENT,
            'sent_at' => now(),
            'error' => null,
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => EmailDeliveryStatus::FAILED,
            'failed_at' => now(),
            // Bounded: driver exceptions can carry very long payloads, and this
            // string is rendered straight into the UI.
            'error' => mb_substr($error, 0, 1000),
        ]);
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new OrganizationScope);
    }
}
