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

test('store redirects to the edit page with the send flag when saving and sending', function () {
    $this->post(route('invoices.store').'?send=1', [
        'type' => 'invoice',
        'organization_id' => $this->organization->id,
        'customer_id' => $this->customer->id,
        'organization_location_id' => $this->organization->primaryLocation->id,
        'customer_location_id' => $this->customer->primaryLocation->id,
        'customer_shipping_location_id' => $this->customer->primaryLocation->id,
        'status' => 'draft',
        'issued_at' => now()->format('Y-m-d'),
        'due_at' => now()->addDays(30)->format('Y-m-d'),
        'items' => [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 5000, 'tax_rate' => 0],
        ],
    ])->assertRedirect(route('invoices.edit', ['invoice' => Invoice::first()->id, 'send' => 1]));
});

test('store redirects to the edit page without the send flag on a plain save', function () {
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
        'items' => [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 5000, 'tax_rate' => 0],
        ],
    ])->assertRedirect(route('invoices.edit', Invoice::first()->id));
});

test('store persists uploaded attachments to the invoice', function () {
    Storage::fake('s3');

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
        'items' => [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 5000, 'tax_rate' => 0],
        ],
        'attachments' => [
            UploadedFile::fake()->create('spec.pdf', 200, 'application/pdf'),
            UploadedFile::fake()->image('diagram.png'),
        ],
    ])->assertRedirect();

    $invoice = Invoice::first();
    expect($invoice->getMedia('attachments'))->toHaveCount(2);
    expect($invoice->attachmentsForDisplay())->toHaveCount(2);
});

test('store rejects an unsupported attachment type', function () {
    Storage::fake('s3');

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
        'items' => [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 5000, 'tax_rate' => 0],
        ],
        'attachments' => [UploadedFile::fake()->create('malware.exe', 10)],
    ])->assertSessionHasErrors('attachments.0');

    expect(Invoice::count())->toBe(0);
});

test('update adds new attachments and removes selected ones', function () {
    Storage::fake('s3');

    $invoice = createInvoiceWithItems([], null, $this->organization, $this->customer);
    $invoice->addMedia(UploadedFile::fake()->create('old.pdf', 100, 'application/pdf'))->toMediaCollection('attachments');
    $removeId = $invoice->getMedia('attachments')->first()->id;

    $this->put(route('invoices.update', $invoice->id), [
        'type' => 'invoice',
        'customer_id' => $this->customer->id,
        'organization_location_id' => $this->organization->primaryLocation->id,
        'customer_location_id' => $this->customer->primaryLocation->id,
        'customer_shipping_location_id' => $this->customer->primaryLocation->id,
        'status' => 'sent',
        'issued_at' => now()->format('Y-m-d'),
        'due_at' => now()->addDays(15)->format('Y-m-d'),
        'items' => [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 5000, 'tax_rate' => 0],
        ],
        'removed_attachment_ids' => [$removeId],
        'attachments' => [UploadedFile::fake()->create('new.pdf', 100, 'application/pdf')],
    ])->assertRedirect();

    $media = $invoice->fresh()->getMedia('attachments');
    expect($media)->toHaveCount(1);
    expect($media->first()->file_name)->toBe('new.pdf');
});

test('show attachment redirects for the owner', function () {
    Storage::fake('s3');

    $invoice = createInvoiceWithItems([], null, $this->organization, $this->customer);
    $invoice->addMedia(UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'))->toMediaCollection('attachments');
    $media = $invoice->getMedia('attachments')->first();

    $this->get(route('invoices.attachments.show', ['invoice' => $invoice->id, 'media' => $media->id]))
        ->assertRedirect();
});

test('show attachment 404s for media that belongs to another invoice', function () {
    Storage::fake('s3');

    $invoiceA = createInvoiceWithItems([], null, $this->organization, $this->customer);
    $invoiceB = createInvoiceWithItems([], null, $this->organization, $this->customer);
    $invoiceB->addMedia(UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'))->toMediaCollection('attachments');
    $foreignMedia = $invoiceB->getMedia('attachments')->first();

    $this->get(route('invoices.attachments.show', ['invoice' => $invoiceA->id, 'media' => $foreignMedia->id]))
        ->assertNotFound();
});

test('store persists the place of supply', function () {
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
        'place_of_supply' => '27',
        'items' => [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 5000, 'tax_rate' => 0],
        ],
    ])->assertRedirect();

    expect(Invoice::first()->place_of_supply)->toBe('27');
});

test('store persists the order number', function () {
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
        'order_number' => 'PO-2026-4471',
        'items' => [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 5000, 'tax_rate' => 0],
        ],
    ])->assertRedirect();

    expect(Invoice::first()->order_number)->toBe('PO-2026-4471');
});

test('update persists the order number', function () {
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
        'order_number' => 'PO-9988',
        'items' => [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 5000, 'tax_rate' => 0],
        ],
    ])->assertRedirect();

    expect($invoice->fresh()->order_number)->toBe('PO-9988');
});

test('store rejects an order number that is too long', function () {
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
        'order_number' => str_repeat('A', 256),
        'items' => [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 5000, 'tax_rate' => 0],
        ],
    ])->assertSessionHasErrors('order_number');
});

test('overriding place of supply on an invoice does not change the customer', function () {
    $this->customer->update(['place_of_supply' => '27']);
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
        'place_of_supply' => '29',
        'items' => [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 5000, 'tax_rate' => 0],
        ],
    ])->assertRedirect();

    expect($invoice->fresh()->place_of_supply)->toBe('29');
    // The customer's default place of supply is untouched.
    expect($this->customer->fresh()->place_of_supply)->toBe('27');
});

test('store clears place of supply for an overseas customer', function () {
    $this->customer->update(['gst_treatment' => 'overseas']);

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
        'place_of_supply' => '27',
        'items' => [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 5000, 'tax_rate' => 0],
        ],
    ])->assertRedirect();

    expect(Invoice::first()->place_of_supply)->toBeNull();
});

test('store rejects an invalid place of supply code', function () {
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
        'place_of_supply' => '99',
        'items' => [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 5000, 'tax_rate' => 0],
        ],
    ])->assertSessionHasErrors('place_of_supply');
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

test('send email remembers the sent recipients as checked', function () {
    Mail::fake();

    $invoice = createInvoiceWithItems(
        ['email_recipients' => []],
        null,
        $this->organization,
        $this->customer,
    );

    $this->post(route('invoices.send-email', $invoice->id), [
        'recipients' => ['billing@example.test', 'ops@example.test'],
        'subject' => 'Test',
        'body' => 'Body',
    ])->assertRedirect();

    Mail::assertQueued(
        DocumentMailer::class,
        fn (DocumentMailer $mail) => $mail->recipients->getEmails() === ['billing@example.test', 'ops@example.test'],
    );

    expect($invoice->fresh()->email_recipients)->toBe([
        ['email' => 'billing@example.test', 'checked' => true],
        ['email' => 'ops@example.test', 'checked' => true],
    ]);
});

test('send email keeps unsent remembered recipients but unchecks them', function () {
    Mail::fake();

    $invoice = createInvoiceWithItems(
        ['email_recipients' => [
            ['email' => 'a@example.test', 'checked' => true],
            ['email' => 'b@example.test', 'checked' => true],
        ]],
        null,
        $this->organization,
        $this->customer,
    );

    $this->post(route('invoices.send-email', $invoice->id), [
        'recipients' => ['a@example.test'],
        'subject' => 'Test',
        'body' => 'Body',
    ])->assertRedirect();

    expect($invoice->fresh()->email_recipients)->toBe([
        ['email' => 'a@example.test', 'checked' => true],
        ['email' => 'b@example.test', 'checked' => false],
    ]);
});

test('send email stores a newly added editor address as a checked recipient', function () {
    Mail::fake();

    $invoice = createInvoiceWithItems(
        ['email_recipients' => [['email' => 'a@example.test', 'checked' => true]]],
        null,
        $this->organization,
        $this->customer,
    );

    $this->post(route('invoices.send-email', $invoice->id), [
        'recipients' => ['a@example.test', 'new@example.test'],
        'subject' => 'Test',
        'body' => 'Body',
    ])->assertRedirect();

    expect($invoice->fresh()->email_recipients)->toBe([
        ['email' => 'a@example.test', 'checked' => true],
        ['email' => 'new@example.test', 'checked' => true],
    ]);
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

test('send email persists uploaded attachments to the invoice and forwards them to the mailer', function () {
    Mail::fake();
    Storage::fake('s3');

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

    // Files uploaded in the email editor are now stored on the invoice itself.
    expect($invoice->fresh()->getMedia('attachments'))->toHaveCount(2);
});

test('send email includes selected invoice attachments and excludes removed ones', function () {
    Mail::fake();
    Storage::fake('s3');

    $invoice = createInvoiceWithItems([], null, $this->organization, $this->customer);
    $invoice->addMedia(UploadedFile::fake()->create('keep.pdf', 100, 'application/pdf'))->toMediaCollection('attachments');
    $invoice->addMedia(UploadedFile::fake()->create('drop.pdf', 100, 'application/pdf'))->toMediaCollection('attachments');

    $keepId = $invoice->getMedia('attachments')->firstWhere('file_name', 'keep.pdf')->id;

    $this->post(route('invoices.send-email', $invoice->id), [
        'recipients' => ['test@example.test'],
        'subject' => 'Test',
        'body' => 'Body',
        'attach_pdf' => false,
        'attachment_ids' => [$keepId],
    ])->assertRedirect();

    Mail::assertQueued(DocumentMailer::class, function (DocumentMailer $mail) {
        return count($mail->extraAttachments) === 1
            && $mail->extraAttachments[0]['name'] === 'keep.pdf';
    });

    // Excluding "drop.pdf" from the send must not delete it from the invoice.
    expect($invoice->fresh()->getMedia('attachments'))->toHaveCount(2);
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

test('store persists the email recipients', function () {
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
        'email_recipients' => [
            ['email' => 'billing@example.test', 'checked' => true],
            ['email' => 'muted@example.test', 'checked' => false],
        ],
        'items' => [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 0],
        ],
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(Invoice::first()->email_recipients)->toBe([
        ['email' => 'billing@example.test', 'checked' => true],
        ['email' => 'muted@example.test', 'checked' => false],
    ]);
});

test('store dedupes email recipients case-insensitively', function () {
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
        'email_recipients' => [
            ['email' => 'Dup@example.test', 'checked' => true],
            ['email' => 'dup@example.test', 'checked' => false],
        ],
        'items' => [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 0],
        ],
    ])->assertRedirect()->assertSessionHasNoErrors();

    // First occurrence wins; the lowercased duplicate is dropped.
    expect(Invoice::first()->email_recipients)->toBe([
        ['email' => 'Dup@example.test', 'checked' => true],
    ]);
});

test('update persists the email recipients', function () {
    $invoice = createInvoiceWithItems([], null, $this->organization, $this->customer);

    $this->put(route('invoices.update', $invoice->id), [
        'type' => 'invoice',
        'customer_id' => $this->customer->id,
        'organization_location_id' => $this->organization->primaryLocation->id,
        'customer_location_id' => $this->customer->primaryLocation->id,
        'customer_shipping_location_id' => $this->customer->primaryLocation->id,
        'status' => 'sent',
        'issued_at' => now()->format('Y-m-d'),
        'due_at' => now()->addDays(30)->format('Y-m-d'),
        'email_recipients' => [
            ['email' => 'new@example.test', 'checked' => true],
        ],
        'items' => [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 0],
        ],
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($invoice->fresh()->email_recipients)->toBe([
        ['email' => 'new@example.test', 'checked' => true],
    ]);
});

test('store computes and persists the intra-state CGST/SGST breakdown', function () {
    // Supplier and place of supply both Maharashtra (27) → intra-state.
    $this->organization->primaryLocation->update(['tax_id' => '27ABCDE1234F1Z5']);

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
        'place_of_supply' => '27',
        'items' => [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 1800],
        ],
    ])->assertRedirect();

    $invoice = Invoice::first();
    expect($invoice->tax_type)->toBe('GST');
    expect($invoice->tax_breakdown)->toBe([
        'CGST @ 9%' => 9000,
        'SGST @ 9%' => 9000,
    ]);
    expect(array_sum($invoice->tax_breakdown))->toBe($invoice->tax);
});

test('store computes and persists the inter-state IGST breakdown', function () {
    // Supplier Maharashtra (27), place of supply Karnataka (29) → inter-state.
    $this->organization->primaryLocation->update(['tax_id' => '27ABCDE1234F1Z5']);

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
        'place_of_supply' => '29',
        'items' => [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 1800],
        ],
    ])->assertRedirect();

    $invoice = Invoice::first();
    expect($invoice->tax_breakdown)->toBe(['IGST @ 18%' => 18000]);
});

test('store leaves the breakdown null for a non-GST supplier', function () {
    // Default org location has no GSTIN, so there is no GST split.
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
        'items' => [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 1800],
        ],
    ])->assertRedirect();

    $invoice = Invoice::first();
    expect($invoice->tax_breakdown)->toBeNull();
    expect($invoice->tax_type)->toBeNull();
});

test('store computes IGST for an overseas customer', function () {
    // Indian GST supplier billing a US customer → export → inter-state IGST.
    $this->organization->primaryLocation->update(['tax_id' => '27ABCDE1234F1Z5']);
    $foreign = createCustomerWithLocation([], ['country' => 'US'], $this->organization);

    $this->post(route('invoices.store'), [
        'type' => 'invoice',
        'organization_id' => $this->organization->id,
        'customer_id' => $foreign->id,
        'organization_location_id' => $this->organization->primaryLocation->id,
        'customer_location_id' => $foreign->primaryLocation->id,
        'customer_shipping_location_id' => $foreign->primaryLocation->id,
        'status' => 'draft',
        'issued_at' => now()->format('Y-m-d'),
        'due_at' => now()->addDays(30)->format('Y-m-d'),
        'items' => [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 1800],
        ],
    ])->assertRedirect();

    $invoice = Invoice::first();
    expect($invoice->tax_breakdown)->toBe(['IGST @ 18%' => 18000]);
    expect($invoice->tax_type)->toBe('GST');
});

test('store computes and persists TDS and TCS adjusting the total', function () {
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
        'tds_rate' => 1000,      // 10%
        'tds_section' => '194J',
        'tcs_rate' => 10,        // 0.1%
        'tcs_section' => '206C(1H)',
        'items' => [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 0],
        ],
    ])->assertRedirect();

    $invoice = Invoice::first();
    expect($invoice->subtotal)->toBe(100000);
    expect($invoice->tds_amount)->toBe(10000);
    expect($invoice->tds_section)->toBe('194J');
    expect($invoice->tcs_amount)->toBe(100); // 0.1% of 100000
    expect($invoice->tcs_section)->toBe('206C(1H)');
    expect($invoice->total)->toBe(90100); // 100000 + 100 - 10000
});

test('update recomputes the total when TDS is applied', function () {
    $invoice = createInvoiceWithItems([], [
        ['description' => 'Item', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 0],
    ], $this->organization, $this->customer);

    $this->put(route('invoices.update', $invoice->id), [
        'type' => 'invoice',
        'customer_id' => $this->customer->id,
        'organization_location_id' => $this->organization->primaryLocation->id,
        'customer_location_id' => $this->customer->primaryLocation->id,
        'customer_shipping_location_id' => $this->customer->primaryLocation->id,
        'status' => 'draft',
        'issued_at' => now()->format('Y-m-d'),
        'due_at' => now()->addDays(30)->format('Y-m-d'),
        'tds_rate' => 1000,
        'tds_section' => '194C',
        'items' => [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 0],
        ],
    ])->assertRedirect();

    $fresh = $invoice->fresh();
    expect($fresh->tds_amount)->toBe(10000);
    expect($fresh->total)->toBe(90000); // 100000 - 10000
});
