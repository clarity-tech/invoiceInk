<?php

use App\Models\User;

/**
 * The detail page and the edit page are the same document under two headings,
 * and a user moves between them constantly. Anything they share has to be in
 * the same place on both, or following the link makes the controls jump.
 */
const HEADER_PROBE = <<<'JS'
function () {
    const seen = {};
    Array.from(document.querySelectorAll('button, a')).forEach(function (el) {
        const label = el.textContent.trim().replace(/\s+/g, ' ');
        const rect = el.getBoundingClientRect();
        if (rect.width === 0) {
            return;
        }
        if (['View public', 'Download PDF', 'Record payment', 'Send', 'Edit'].indexOf(label) !== -1) {
            seen[label] = {
                right: Math.round(rect.right),
                top: Math.round(rect.top),
                height: Math.round(rect.height),
            };
        }
    });

    const anchor = Array.from(document.querySelectorAll('button, a')).find(function (el) {
        return el.textContent.trim().replace(/\s+/g, ' ') === 'Download PDF';
    });
    const row = anchor ? anchor.closest('.justify-between') : null;

    return {
        rowHeight: row ? Math.round(row.getBoundingClientRect().height) : null,
        buttons: seen,
        // Where the page body starts: if the action row wraps on one page and
        // not the other, everything below it moves down.
        bodyTop: Math.round(document.querySelector('#main-content').getBoundingClientRect().top),
    };
}
JS;

it('keeps the shared controls in the same place on the detail and edit pages', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'header-consistency@example.test',
    ]);

    $org = createOrganizationWithLocation([
        'company_name' => 'Clarity Technologies',
        'currency' => 'INR',
        'country_code' => 'IN',
        'setup_completed_at' => now(),
    ], [], $user);

    $customer = createCustomerWithLocation(['name' => 'Sandline Pharmacy LLC'], [], $org);

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'CT-INV-HEADER',
        'status' => 'sent',
        'amount_paid' => 0,
        'issued_at' => now()->subDays(6),
        'due_at' => now()->addDays(24),
    ], null, $org, $customer);

    $this->actingAs($user);

    $detail = visit("/invoices/{$invoice->id}")->resize(1440, 1000)->script(HEADER_PROBE);
    $edit = visit("/invoices/{$invoice->id}/edit")->resize(1440, 1000)->script(HEADER_PROBE);

    // Both pages offer these two, so both must put them in the same place.
    foreach (['View public', 'Download PDF'] as $label) {
        expect($detail['buttons'])->toHaveKey($label);
        expect($edit['buttons'])->toHaveKey($label);

        expect(abs($detail['buttons'][$label]['right'] - $edit['buttons'][$label]['right']))
            ->toBeLessThanOrEqual(1, "{$label} moves sideways between the two pages");
        expect(abs($detail['buttons'][$label]['top'] - $edit['buttons'][$label]['top']))
            ->toBeLessThanOrEqual(1, "{$label} moves up or down between the two pages");
    }

    // The cause, rather than the symptom: the edit page's status control is
    // taller than the detail page's badge, so without a floor on the row the
    // shared buttons centred at two different heights.
    expect($detail['rowHeight'])->toBe($edit['rowHeight']);

    // And the document underneath starts at the same height, which it does not
    // if one page's action row is taller or wraps onto a second line.
    expect(abs($detail['bodyTop'] - $edit['bodyTop']))
        ->toBeLessThanOrEqual(1, 'the page body starts at a different height on the two pages');
});
