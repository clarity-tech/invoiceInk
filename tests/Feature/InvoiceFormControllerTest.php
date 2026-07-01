<?php

use App\Contracts\Services\PdfServiceInterface;
use App\Enums\InvoiceStatus;
use App\Mail\DocumentMailer;
use App\Models\Invoice;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->organization = createOrganizationWithLocation();
    $this->actingAs($this->organization->owner);
    $this->customer = createCustomerWithLocation([], [], $this->organization);
});

test('create page renders with correct props', function () {
    $this->get(route('invoices.create'))
        ->assertStatus(200)
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Invoices/Create')
            ->has('customers')
            ->has('organizationLocations')
            ->has('taxTemplates')
            ->has('numberingSeries')
            ->has('statusOptions')
            ->has('defaults')
            ->where('taxLabels.IN', 'GSTIN')
            ->where('taxLabels.US', 'EIN')
            ->where('type', 'invoice')
        );
});

test('create page renders estimate type for estimates route', function () {
    $this->get(route('estimates.create'))
        ->assertStatus(200)
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Invoices/Create')
            ->where('type', 'estimate')
        );
});

test('store creates invoice with items', function () {
    expect(Invoice::count())->toBe(0);

    $this->post(route('invoices.store'), [
        'type' => 'invoice',
        'organization_id' => $this->organization->id,
        'customer_id' => $this->customer->id,
        'organization_location_id' => $this->organization->primaryLocation->id,
        'customer_location_id' => $this->customer->primaryLocation->id,
        'customer_shipping_location_id' => $this->customer->primaryLocation->id,
        'status' => 'draft',
        'issued_at' => now()->format('Y-m-d'),
        'due_at' => now()->addDays(30)->format('Y-m-d'),
        'notes' => 'Test notes',
        'items' => [
            [
                'description' => 'Test Service',
                'sac_code' => '998311',
                'quantity' => 2,
                'unit_price' => 5000,
                'tax_rate' => 1800,
            ],
        ],
    ])->assertRedirect();

    expect(Invoice::count())->toBe(1);

    $invoice = Invoice::first();
    expect($invoice->type)->toBe('invoice');
    expect($invoice->organization_id)->toBe($this->organization->id);
    expect($invoice->customer_id)->toBe($this->customer->id);
    expect($invoice->items)->toHaveCount(1);
    expect($invoice->items->first()->description)->toBe('Test Service');
    expect($invoice->items->first()->unit_price)->toBe(5000);
    expect($invoice->items->first()->tax_rate)->toBe(1800);
});

test('store creates estimate', function () {
    $this->post(route('invoices.store'), [
        'type' => 'estimate',
        'organization_id' => $this->organization->id,
        'customer_id' => $this->customer->id,
        'organization_location_id' => $this->organization->primaryLocation->id,
        'customer_location_id' => $this->customer->primaryLocation->id,
        'customer_shipping_location_id' => $this->customer->primaryLocation->id,
        'status' => 'draft',
        'issued_at' => now()->format('Y-m-d'),
        'due_at' => now()->addDays(30)->format('Y-m-d'),
        'items' => [
            [
                'description' => 'Estimate Item',
                'quantity' => 1,
                'unit_price' => 10000,
                'tax_rate' => 0,
            ],
        ],
    ])->assertRedirect();

    $invoice = Invoice::first();
    expect($invoice->type)->toBe('estimate');
});

test('store validates required fields', function () {
    // Need organization_id to pass the abort_unless check
    $this->post(route('invoices.store'), [
        'organization_id' => $this->organization->id,
    ])->assertSessionHasErrors([
        'type',
        'customer_id',
        'organization_location_id',
        'customer_location_id',
        'customer_shipping_location_id',
        'status',
        'issued_at',
        'due_at',
        'items',
    ]);
});

test('store requires due date on or after issue date', function () {
    $this->post(route('invoices.store'), [
        'type' => 'invoice',
        'organization_id' => $this->organization->id,
        'customer_id' => $this->customer->id,
        'organization_location_id' => $this->organization->primaryLocation->id,
        'customer_location_id' => $this->customer->primaryLocation->id,
        'customer_shipping_location_id' => $this->customer->primaryLocation->id,
        'status' => 'draft',
        'issued_at' => now()->format('Y-m-d'),
        'due_at' => now()->subDay()->format('Y-m-d'),
        'items' => [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 10000, 'tax_rate' => 0],
        ],
    ])->assertSessionHasErrors(['due_at']);
});

test('store validates items', function () {
    $this->post(route('invoices.store'), [
        'type' => 'invoice',
        'organization_id' => $this->organization->id,
        'customer_id' => $this->customer->id,
        'organization_location_id' => $this->organization->primaryLocation->id,
        'customer_location_id' => $this->customer->primaryLocation->id,
        'customer_shipping_location_id' => $this->customer->primaryLocation->id,
        'status' => 'draft',
        'items' => [
            [
                'description' => '',
                'quantity' => -1,
                'unit_price' => -100,
            ],
        ],
    ])->assertSessionHasErrors([
        'items.0.description',
        'items.0.quantity',
        'items.0.unit_price',
    ]);
});

test('store prevents unauthenticated access', function () {
    auth()->logout();

    $this->post(route('invoices.store'), [
        'type' => 'invoice',
        'organization_id' => $this->organization->id,
        'customer_id' => $this->customer->id,
        'organization_location_id' => $this->organization->primaryLocation->id,
        'customer_location_id' => $this->customer->primaryLocation->id,
        'customer_shipping_location_id' => $this->customer->primaryLocation->id,
        'status' => 'draft',
        'items' => [
            ['description' => 'Test', 'quantity' => 1, 'unit_price' => 100, 'tax_rate' => 0],
        ],
    ])->assertRedirect('/login');

    expect(Invoice::count())->toBe(0);
});

test('edit page renders with invoice data', function () {
    $invoice = createInvoiceWithItems([], null, $this->organization, $this->customer);

    $this->get(route('invoices.edit', $invoice->id))
        ->assertStatus(200)
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Invoices/Edit')
            ->has('invoice')
            ->has('customers')
            ->has('organizationLocations')
            ->has('statusOptions')
            ->where('taxLabels.IN', 'GSTIN')
            ->where('taxLabels.US', 'EIN')
            ->where('invoice.id', $invoice->id)
        );
});

test('edit page denies access when not authenticated', function () {
    $invoice = createInvoiceWithItems([], null, $this->organization, $this->customer);

    // Log out and try to access
    auth()->logout();

    $this->get(route('invoices.edit', $invoice->id))
        ->assertRedirect('/login');
});

test('update modifies invoice and items', function () {
    $invoice = createInvoiceWithItems([], null, $this->organization, $this->customer);

    $this->put(route('invoices.update', $invoice->id), [
        'type' => 'invoice',
        'customer_id' => $this->customer->id,
        'organization_location_id' => $this->organization->primaryLocation->id,
        'customer_location_id' => $this->customer->primaryLocation->id,
        'customer_shipping_location_id' => $this->customer->primaryLocation->id,
        'status' => 'sent',
        'issued_at' => now()->format('Y-m-d'),
        'due_at' => now()->addDays(15)->format('Y-m-d'),
        'notes' => 'Updated notes',
        'items' => [
            [
                'description' => 'Updated Item',
                'quantity' => 3,
                'unit_price' => 8000,
                'tax_rate' => 900,
            ],
        ],
    ])->assertRedirect();

    $invoice->refresh();
    expect($invoice->status->value)->toBe('sent');
    expect($invoice->notes)->toBe('Updated notes');
    expect($invoice->items)->toHaveCount(1);
    expect($invoice->items->first()->description)->toBe('Updated Item');
    expect($invoice->items->first()->unit_price)->toBe(8000);
});

test('update validates required fields', function () {
    $invoice = createInvoiceWithItems([], null, $this->organization, $this->customer);

    $this->put(route('invoices.update', $invoice->id), [])
        ->assertSessionHasErrors([
            'type',
            'customer_id',
            'organization_location_id',
            'customer_location_id',
            'customer_shipping_location_id',
            'status',
            'issued_at',
            'due_at',
            'items',
        ]);
});

test('send email works', function () {
    Mail::fake();

    $invoice = createInvoiceWithItems([], null, $this->organization, $this->customer);

    // Mock PDF service to avoid actual PDF generation
    $this->mock(PdfServiceInterface::class, function ($mock) {
        $mock->shouldReceive('generateInvoicePdf')->andReturn('fake-pdf-content');
    });

    $this->post(route('invoices.send-email', $invoice->id), [
        'recipients' => ['test@example.test'],
        'cc' => ['cc@example.test'],
        'subject' => 'Test Invoice',
        'body' => 'Please find attached.',
        'attach_pdf' => true,
    ])->assertRedirect();
});

test('send email promotes draft invoice to sent', function () {
    Mail::fake();

    $invoice = createInvoiceWithItems(
        ['status' => InvoiceStatus::DRAFT],
        null,
        $this->organization,
        $this->customer,
    );

    $this->post(route('invoices.send-email', $invoice->id), [
        'recipients' => ['test@example.test'],
        'subject' => 'Test Invoice',
        'body' => 'Please find attached.',
    ])->assertRedirect();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::SENT);
});

test('send email does not overwrite a non-draft status', function () {
    Mail::fake();

    foreach ([InvoiceStatus::PAID, InvoiceStatus::PARTIALLY_PAID, InvoiceStatus::ACCEPTED, InvoiceStatus::VOID] as $status) {
        $invoice = createInvoiceWithItems(
            ['status' => $status],
            null,
            $this->organization,
            $this->customer,
        );

        $this->post(route('invoices.send-email', $invoice->id), [
            'recipients' => ['test@example.test'],
            'subject' => 'Test Invoice',
            'body' => 'Please find attached.',
        ])->assertRedirect();

        expect($invoice->fresh()->status)->toBe($status);
    }
});

test('send email validates recipients', function () {
    $invoice = createInvoiceWithItems([], null, $this->organization, $this->customer);

    $this->post(route('invoices.send-email', $invoice->id), [
        'recipients' => [],
        'subject' => '',
        'body' => '',
    ])->assertSessionHasErrors(['recipients', 'subject', 'body']);
});

test('send email denies unauthenticated access', function () {
    $invoice = createInvoiceWithItems([], null, $this->organization, $this->customer);

    auth()->logout();

    $this->post(route('invoices.send-email', $invoice->id), [
        'recipients' => ['test@example.test'],
        'subject' => 'Test',
        'body' => 'Body',
    ])->assertRedirect('/login');
});

test('send email stores uploaded attachments and forwards them to the mailer', function () {
    Mail::fake();
    Storage::fake(config('filesystems.default'));

    $invoice = createInvoiceWithItems([], null, $this->organization, $this->customer);

    $this->mock(PdfServiceInterface::class, function ($mock) {
        $mock->shouldReceive('generateInvoicePdf')->andReturn('fake-pdf-content');
    });

    $contract = UploadedFile::fake()->create('contract.pdf', 500, 'application/pdf');
    $photo = UploadedFile::fake()->image('photo.png');

    $this->post(route('invoices.send-email', $invoice->id), [
        'recipients' => ['test@example.test'],
        'cc' => [],
        'subject' => 'Test Invoice',
        'body' => 'Please review.',
        'attach_pdf' => true,
        'attachments' => [$contract, $photo],
    ])->assertRedirect();

    Mail::assertQueued(DocumentMailer::class, function (DocumentMailer $mail) {
        return count($mail->extraAttachments) === 2
            && $mail->extraAttachments[0]['name'] === 'contract.pdf'
            && $mail->extraAttachments[1]['name'] === 'photo.png';
    });

    $disk = Storage::disk(config('filesystems.default'));
    $stored = $disk->allFiles('email-attachments');
    expect($stored)->toHaveCount(2);
});

test('send email rejects too many attachments', function () {
    $invoice = createInvoiceWithItems([], null, $this->organization, $this->customer);

    $files = array_fill(0, 11, UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'));

    $this->post(route('invoices.send-email', $invoice->id), [
        'recipients' => ['test@example.test'],
        'subject' => 'Test',
        'body' => 'Body',
        'attachments' => $files,
    ])->assertSessionHasErrors('attachments');
});

test('send email rejects an oversized attachment', function () {
    $invoice = createInvoiceWithItems([], null, $this->organization, $this->customer);

    $big = UploadedFile::fake()->create('huge.pdf', 11 * 1024, 'application/pdf');

    $this->post(route('invoices.send-email', $invoice->id), [
        'recipients' => ['test@example.test'],
        'subject' => 'Test',
        'body' => 'Body',
        'attachments' => [$big],
    ])->assertSessionHasErrors('attachments.0');
});

test('send email rejects unsupported mime types', function () {
    $invoice = createInvoiceWithItems([], null, $this->organization, $this->customer);

    $exe = UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream');

    $this->post(route('invoices.send-email', $invoice->id), [
        'recipients' => ['test@example.test'],
        'subject' => 'Test',
        'body' => 'Body',
        'attachments' => [$exe],
    ])->assertSessionHasErrors('attachments.0');
});

test('send email defaults include_view_online_button to true when omitted', function () {
    Mail::fake();
    $invoice = createInvoiceWithItems([], null, $this->organization, $this->customer);

    $this->post(route('invoices.send-email', $invoice->id), [
        'recipients' => ['test@example.test'],
        'subject' => 'Test',
        'body' => 'Body',
    ])->assertRedirect();

    Mail::assertQueued(DocumentMailer::class, fn (DocumentMailer $m) => $m->includeViewOnlineButton === true);
});

test('send email omitted toggles fall back to the configured email defaults', function () {
    Mail::fake();
    config([
        'app.invoice_email.include_view_online_button' => false,
        'app.invoice_email.attach_pdf' => false,
    ]);
    $invoice = createInvoiceWithItems([], null, $this->organization, $this->customer);

    $this->post(route('invoices.send-email', $invoice->id), [
        'recipients' => ['test@example.test'],
        'subject' => 'Test',
        'body' => 'Body',
    ])->assertRedirect();

    Mail::assertQueued(
        DocumentMailer::class,
        fn (DocumentMailer $m) => $m->includeViewOnlineButton === false && $m->attachPdf === false,
    );
});

test('send email forwards include_view_online_button=false to the mailer', function () {
    Mail::fake();
    $invoice = createInvoiceWithItems([], null, $this->organization, $this->customer);

    $this->post(route('invoices.send-email', $invoice->id), [
        'recipients' => ['test@example.test'],
        'subject' => 'Test',
        'body' => 'Body',
        'include_view_online_button' => false,
    ])->assertRedirect();

    Mail::assertQueued(DocumentMailer::class, fn (DocumentMailer $m) => $m->includeViewOnlineButton === false);
});

test('send email rejects when combined attachment size exceeds the cap', function () {
    Storage::fake(config('filesystems.default'));
    $invoice = createInvoiceWithItems([], null, $this->organization, $this->customer);

    // 3 files * 10MB = 30MB total, each individually allowed.
    $files = array_map(
        fn ($i) => UploadedFile::fake()->create("file-{$i}.pdf", 10 * 1024, 'application/pdf'),
        range(1, 3),
    );

    $this->post(route('invoices.send-email', $invoice->id), [
        'recipients' => ['test@example.test'],
        'subject' => 'Test',
        'body' => 'Body',
        'attach_pdf' => false,
        'attachments' => $files,
    ])->assertSessionHasErrors('attachments');
});
