<?php

use App\Ai\Agents\InvoiceDrafter;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use App\ValueObjects\ContactCollection;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Prompts\AgentPrompt;

beforeEach(function () {
    config(['ai.providers.anthropic.key' => 'test-key']);
    $this->travelTo('2026-10-16 10:00:00');
});

function draftingOrganization(array $attributes = []): Organization
{
    return createOrganizationWithLocation(array_merge(['ai_drafting_enabled' => true], $attributes));
}

function acmeCustomer(Organization $organization): Customer
{
    return createCustomerWithLocation([
        'name' => 'Acme Industries Pvt Ltd',
        'emails' => new ContactCollection([['name' => 'Accounts', 'email' => 'accounts@acme.test']]),
        'place_of_supply' => '27',
    ], ['tax_id' => '27AAACA1234A1Z5', 'country' => 'IN'], $organization);
}

/**
 * What InvoiceDrafter returns, with only the fields a test cares about set.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function drafterOutput(array $overrides = []): array
{
    return array_merge([
        'customer' => ['name' => null, 'tax_number' => null, 'email' => null],
        'subject' => null,
        'order_number' => null,
        'due_date' => null,
        'payment_terms_days' => null,
        'currency' => null,
        'items' => [],
        'stated_total' => null,
    ], $overrides);
}

function postInvoiceDraft(User $user, array $payload = ['text' => 'Please invoice us for the October retainer.']): TestResponse
{
    return test()->actingAs($user)->from('/invoices/create')->post('/invoices/draft', $payload);
}

/**
 * Draft from a faked reading of the document and open the create form with it.
 *
 * @param  array<string, mixed>  $output
 */
function openDraft(Organization $organization, array $output): TestResponse
{
    InvoiceDrafter::fake([drafterOutput($output)]);

    $response = postInvoiceDraft($organization->owner);
    $response->assertRedirectContains('/invoices/create?draft=');

    return test()->get($response->headers->get('Location'));
}

it('opens the create form filled in from a pasted message, without saving anything', function () {
    $organization = draftingOrganization();
    $customer = acmeCustomer($organization);

    openDraft($organization, [
        'customer' => ['name' => 'ACME Industries Private Limited', 'tax_number' => '27 aaaca1234a1z5', 'email' => null],
        'subject' => 'October development',
        'order_number' => 'PO-4512',
        'payment_terms_days' => 15,
        'items' => [['description' => 'Development', 'sac_code' => '998314', 'quantity' => 40, 'unit_price' => '2,000.00', 'tax_rate_percent' => 18]],
        'stated_total' => '94,400.00',
    ])->assertInertia(fn (Assert $page) => $page
        ->component('Invoices/Create')
        ->where('defaults.customer_id', $customer->id)
        ->where('defaults.customer_location_id', $customer->primary_location_id)
        ->where('defaults.place_of_supply', '27')
        ->where('defaults.email_recipients', [['email' => 'accounts@acme.test', 'checked' => true]])
        ->where('defaults.subject', 'October development')
        ->where('defaults.order_number', 'PO-4512')
        ->where('defaults.issued_at', '2026-10-16')
        ->where('defaults.due_at', '2026-10-31')
        ->where('defaults.items', [['description' => 'Development', 'sac_code' => '998314', 'quantity' => 40, 'unit_price' => 200000, 'tax_rate' => 1800]])
        ->where('draft', ['source' => 'pasted text', 'warnings' => []])
        ->where('aiDrafting', true));

    InvoiceDrafter::assertPrompted(fn (AgentPrompt $prompt) => $prompt->contains('Please invoice us for the October retainer.'));
    expect(Invoice::count())->toBe(0);
    test()->get('/invoices')->assertInertia(fn (Assert $page) => $page->where('aiDrafting', true));
});

it('drafts an estimate from an enquiry onto the estimate form', function () {
    $organization = draftingOrganization();
    InvoiceDrafter::fake([drafterOutput([
        'items' => [['description' => 'Website redesign', 'sac_code' => null, 'quantity' => 1, 'unit_price' => '0', 'tax_rate_percent' => null]],
    ])]);

    $response = postInvoiceDraft($organization->owner, ['type' => 'estimate', 'text' => 'Could you quote us for a website redesign?']);
    $response->assertRedirectContains('/estimates/create?draft=');

    test()->get($response->headers->get('Location'))->assertInertia(fn (Assert $page) => $page
        ->where('type', 'estimate')
        ->where('aiDrafting', true)
        ->where('defaults.items.0.description', 'Website redesign')
        ->where('draft.warnings', fn ($warnings) => collect($warnings)->contains('"Website redesign" has no price yet.')));

    InvoiceDrafter::assertPrompted(fn (AgentPrompt $prompt) => $prompt->contains('Draft the estimate')
        && str_contains((string) $prompt->agent->instructions(), 'valid until'));
});

it('matches the customer by tax number, email or name', function (array $party) {
    $organization = draftingOrganization();
    $customer = acmeCustomer($organization);
    createCustomerWithLocation(['name' => 'Globex Corporation'], [], $organization);

    openDraft($organization, ['customer' => array_merge(['name' => null, 'tax_number' => null, 'email' => null], $party)])
        ->assertInertia(fn (Assert $page) => $page->where('defaults.customer_id', $customer->id));
})->with([
    'tax number' => [['tax_number' => '27aaaca1234a1z5']],
    'email' => [['email' => 'ACCOUNTS@acme.test']],
    'name' => [['name' => 'Acme Industries Ltd.']],
]);

it('leaves the customer to the person when none matches or several do', function (Closure $customers, string $warning) {
    $organization = draftingOrganization();
    $customers($organization);

    openDraft($organization, ['customer' => ['name' => 'Acme Industries', 'tax_number' => '27AAACA1234A1Z5', 'email' => null]])
        ->assertInertia(fn (Assert $page) => $page
            ->missing('defaults.customer_id')
            ->where('draft.warnings.0', $warning));
})->with([
    "only another organization's" => [
        fn (Organization $organization) => acmeCustomer(createOrganizationWithLocation()),
        'No customer matches "Acme Industries". Choose one, or add them under Customers first.',
    ],
    'two by the same name' => [
        function (Organization $organization) {
            createCustomerWithLocation(['name' => 'Acme Industries Ltd'], [], $organization);
            createCustomerWithLocation(['name' => 'ACME Industries Limited'], [], $organization);
        },
        'No customer matches "Acme Industries". Choose one, or add them under Customers first.',
    ],
]);

it('bills a fractional quantity as one line, with the units in its description', function () {
    openDraft(draftingOrganization(), [
        'items' => [['description' => 'Consulting', 'sac_code' => null, 'quantity' => 7.5, 'unit_price' => '2000', 'tax_rate_percent' => 18]],
    ])->assertInertia(fn (Assert $page) => $page
        ->where('defaults.items.0.description', 'Consulting (7.5 × ₹2,000.00)')
        ->where('defaults.items.0.quantity', 1)
        ->where('defaults.items.0.unit_price', 1500000)
        ->where('draft.warnings.1', '"Consulting" is for 7.5 units. Quantities are whole numbers here, so it is one line at the full amount, with the units in its description.'));
});

it('warns about what it could not carry over as written', function (array $output, string $warning) {
    openDraft(draftingOrganization(), $output)
        ->assertInertia(fn (Assert $page) => $page->where('draft.warnings', fn ($warnings) => collect($warnings)->contains($warning)));
})->with([
    'a total the lines do not add up to' => [
        ['items' => [['description' => 'Design', 'sac_code' => null, 'quantity' => 1, 'unit_price' => '500', 'tax_rate_percent' => 0]], 'stated_total' => '1000.00'],
        "The document's total is ₹1,000.00, but these lines add up to ₹500.00. Check the prices, quantities and tax rates.",
    ],
    'a tax rate the organization does not charge' => [
        ['items' => [['description' => 'Design', 'sac_code' => null, 'quantity' => 1, 'unit_price' => '500', 'tax_rate_percent' => 12]]],
        '"Design" states 12% tax, which is not one of your rates, so it uses GST 18% instead.',
    ],
    'a line with no price' => [
        ['items' => [['description' => 'Design', 'sac_code' => null, 'quantity' => 1, 'unit_price' => '0', 'tax_rate_percent' => 18]]],
        '"Design" has no price yet.',
    ],
    'a negative amount' => [
        ['items' => [['description' => 'Discount', 'sac_code' => null, 'quantity' => 1, 'unit_price' => '-500', 'tax_rate_percent' => 0]]],
        'A line could not be read ("Discount"). Add it yourself if it should be billed.',
    ],
    'another currency' => [
        ['currency' => 'usd'],
        'The document is in USD, but the draft is in INR. The amounts are copied as written, so convert them before saving.',
    ],
    'a due date already past' => [
        ['due_date' => '2026-09-30'],
        "The document's due date, 30 Sep 2026, has already passed, so it was not used.",
    ],
]);

it('gives a line with no stated tax rate the rate the organization usually charges', function (Closure $setUp, int $taxRate, string $note) {
    $organization = draftingOrganization();
    $customerName = $setUp($organization);

    openDraft($organization, [
        'customer' => ['name' => $customerName, 'tax_number' => null, 'email' => null],
        'items' => [['description' => 'Support', 'sac_code' => null, 'quantity' => 1, 'unit_price' => '1000', 'tax_rate_percent' => null]],
    ])->assertInertia(fn (Assert $page) => $page
        ->where('defaults.items.0.tax_rate', $taxRate)
        ->where('draft.warnings', fn ($warnings) => collect($warnings)->contains($note)));
})->with([
    'its country standard, before any invoice' => [
        fn (Organization $organization) => null,
        1800,
        'The document does not state the tax rate for every line, so those lines use GST 18%, your usual rate. Change any that differ.',
    ],
    'the rate on most of its lines' => [
        function (Organization $organization) {
            $fivePercent = ['description' => 'Printed manuals', 'quantity' => 1, 'unit_price' => 1000, 'tax_rate' => 500];
            createInvoiceWithItems([], [$fivePercent, $fivePercent], $organization);
            createInvoiceWithItems([], [['description' => 'Consulting', 'quantity' => 1, 'unit_price' => 1000, 'tax_rate' => 1800]], $organization);
        },
        500,
        'The document does not state the tax rate for every line, so those lines use GST 5%, your usual rate. Change any that differ.',
    ],
    'zero for an overseas customer' => [
        function (Organization $organization) {
            createCustomerWithLocation(['name' => 'Gulf Horizon Trading LLC', 'gst_treatment' => 'overseas'], ['country' => 'AE'], $organization);

            return 'Gulf Horizon Trading LLC';
        },
        0,
        'The document does not state the tax rate for every line. This customer is overseas, so those lines are at GST 0%, as exports usually are.',
    ],
]);

it('takes the due date from the document, then its payment terms', function (array $output, string $dueAt) {
    openDraft(draftingOrganization(), $output)
        ->assertInertia(fn (Assert $page) => $page->where('defaults.due_at', $dueAt));
})->with([
    'a stated date' => [['due_date' => '2026-12-01', 'payment_terms_days' => 7], '2026-12-01'],
    'payment terms' => [['payment_terms_days' => 7], '2026-10-23'],
    'neither' => [[], '2026-11-15'],
    'a date that does not exist' => [['due_date' => '2026-02-30'], '2026-11-15'],
]);

it('sends an attached file to be read', function () {
    $organization = draftingOrganization();
    InvoiceDrafter::fake([drafterOutput()]);

    postInvoiceDraft($organization->owner, ['file' => UploadedFile::fake()->createWithContent('po-4512.pdf', "%PDF-1.4\n1 0 obj << >> endobj\n%%EOF")])
        ->assertRedirectContains('/invoices/create?draft=');

    InvoiceDrafter::assertPrompted(fn (AgentPrompt $prompt) => $prompt->attachments->count() === 1);
});

it('asks Claude for the draft in the shape it reads back, with the file attached', function (UploadedFile $file, string $block) {
    $organization = draftingOrganization();
    Http::fake(['api.anthropic.com/*' => Http::response([
        'id' => 'msg_1',
        'type' => 'message',
        'role' => 'assistant',
        'model' => 'claude-haiku-5-5',
        'content' => [['type' => 'text', 'text' => json_encode(drafterOutput(['order_number' => 'PO-4512']))]],
        'stop_reason' => 'end_turn',
        'usage' => ['input_tokens' => 1500, 'output_tokens' => 120],
    ])]);

    $location = postInvoiceDraft($organization->owner, ['text' => 'Invoice for PO-4512.', 'file' => $file])->headers->get('Location');

    test()->get($location)->assertInertia(fn (Assert $page) => $page->where('defaults.order_number', 'PO-4512'));
    Http::assertSent(fn (Request $request) => $request['model'] === 'claude-haiku-5-5'
        && $request['output_config']['format']['type'] === 'json_schema'
        && $request['messages'][0]['content'][0]['type'] === $block);
})->with([
    'a PDF' => [fn () => UploadedFile::fake()->createWithContent('po.pdf', "%PDF-1.4\n%%EOF"), 'document'],
    'a photo' => [fn () => UploadedFile::fake()->image('po.jpg', 800, 600), 'image'],
]);

it('needs a message or a PDF or image', function (array $payload, string $field) {
    InvoiceDrafter::fake()->preventStrayPrompts();

    postInvoiceDraft(draftingOrganization()->owner, $payload)->assertSessionHasErrors($field);

    InvoiceDrafter::assertNeverPrompted();
})->with([
    'nothing' => [[], 'text'],
    'a spreadsheet' => [['file' => UploadedFile::fake()->create('hours.xlsx', 10)], 'file'],
    'a document type other than invoice or estimate' => [['text' => 'Quote us for a website.', 'type' => 'receipt'], 'type'],
    'a photo over 7 MB' => [['file' => UploadedFile::fake()->image('po.jpg')->size(8000)], 'file'],
]);

it('says so when the document cannot be read', function () {
    InvoiceDrafter::fake(fn () => throw new ProviderOverloadedException('Overloaded'));

    postInvoiceDraft(draftingOrganization()->owner)
        ->assertRedirect('/invoices/create')
        ->assertSessionHasErrors(['draft' => 'The document could not be read just now. Try again, or fill in the form yourself.']);
});

it('is offered only to organizations that opted in, once a key is set', function (array $attributes, ?string $key) {
    config(['ai.providers.anthropic.key' => $key]);
    $organization = draftingOrganization($attributes);
    InvoiceDrafter::fake()->preventStrayPrompts();

    test()->actingAs($organization->owner)->get('/invoices/create')
        ->assertInertia(fn (Assert $page) => $page->where('aiDrafting', false));
    test()->get('/invoices')
        ->assertInertia(fn (Assert $page) => $page->where('aiDrafting', false));
    postInvoiceDraft($organization->owner)->assertForbidden();
})->with([
    'not opted in' => [['ai_drafting_enabled' => false], 'test-key'],
    'no key' => [[], null],
]);

it('refuses a member who cannot create invoices', function () {
    $organization = draftingOrganization();
    $viewer = User::factory()->create();
    $organization->users()->attach($viewer, ['role' => 'viewer']);
    $viewer->switchTeam($organization);
    InvoiceDrafter::fake()->preventStrayPrompts();

    postInvoiceDraft($viewer)->assertForbidden();
});

it('limits how many drafts a person makes in an hour', function () {
    config(['services.invoice_drafts.per_hour' => 1]);
    $organization = draftingOrganization();
    InvoiceDrafter::fake([drafterOutput()]);

    postInvoiceDraft($organization->owner)->assertRedirectContains('/invoices/create?draft=');
    postInvoiceDraft($organization->owner)
        ->assertSessionHasErrors(['draft' => 'You have drafted a lot of invoices in the last hour. Try again later.']);
});

it("does not show a person someone else's draft", function () {
    $organization = draftingOrganization();
    $customer = acmeCustomer($organization);
    $colleague = User::factory()->create();
    $organization->users()->attach($colleague, ['role' => 'admin']);
    $colleague->switchTeam($organization);
    InvoiceDrafter::fake([drafterOutput(['customer' => ['name' => $customer->name, 'tax_number' => null, 'email' => null]])]);

    $location = postInvoiceDraft($organization->owner)->headers->get('Location');

    test()->flushSession()->actingAs($colleague)->get($location)
        ->assertInertia(fn (Assert $page) => $page->where('draft', null)->missing('defaults.customer_id'));
});
