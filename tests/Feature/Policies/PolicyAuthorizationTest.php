<?php

use App\Models\Customer;
use App\Models\InvoiceNumberingSeries;
use App\Models\Organization;
use App\Models\TaxTemplate;

beforeEach(function () {
    $this->owner = createUserWithTeam();
    $this->organization = createOrganizationWithLocation([], [], $this->owner);

    $this->otherUser = createUserWithTeam();
    $this->otherOrganization = createOrganizationWithLocation([], [], $this->otherUser);
});

// --- TeamPolicy ---

describe('TeamPolicy', function () {
    it('allows owner to view their organization', function () {
        expect($this->owner->can('view', $this->organization))->toBeTrue();
    });

    it('denies other user from viewing organization', function () {
        expect($this->otherUser->can('view', $this->organization))->toBeFalse();
    });

    it('allows owner to update their organization', function () {
        expect($this->owner->can('update', $this->organization))->toBeTrue();
    });

    it('denies other user from updating organization', function () {
        expect($this->otherUser->can('update', $this->organization))->toBeFalse();
    });

    it('allows owner to delete their organization', function () {
        expect($this->owner->can('delete', $this->organization))->toBeTrue();
    });

    it('denies other user from deleting organization', function () {
        expect($this->otherUser->can('delete', $this->organization))->toBeFalse();
    });
});

// --- InvoicePolicy ---

describe('InvoicePolicy', function () {
    beforeEach(function () {
        $this->invoice = createInvoiceWithItems([], null, $this->organization);
    });

    it('allows owner to view their invoice', function () {
        expect($this->owner->can('view', $this->invoice))->toBeTrue();
    });

    it('denies other user from viewing invoice', function () {
        expect($this->otherUser->can('view', $this->invoice))->toBeFalse();
    });

    it('allows owner to update their invoice', function () {
        expect($this->owner->can('update', $this->invoice))->toBeTrue();
    });

    it('denies other user from updating invoice', function () {
        expect($this->otherUser->can('update', $this->invoice))->toBeFalse();
    });

    it('allows owner to delete their invoice', function () {
        expect($this->owner->can('delete', $this->invoice))->toBeTrue();
    });

    it('denies other user from deleting invoice', function () {
        expect($this->otherUser->can('delete', $this->invoice))->toBeFalse();
    });
});

// --- CustomerPolicy ---

describe('CustomerPolicy', function () {
    beforeEach(function () {
        $this->customer = createCustomerWithLocation([], [], $this->organization);
    });

    it('allows owner to view their customer', function () {
        expect($this->owner->can('view', $this->customer))->toBeTrue();
    });

    it('denies other user from viewing customer', function () {
        expect($this->otherUser->can('view', $this->customer))->toBeFalse();
    });

    it('allows owner to update their customer', function () {
        expect($this->owner->can('update', $this->customer))->toBeTrue();
    });

    it('denies other user from updating customer', function () {
        expect($this->otherUser->can('update', $this->customer))->toBeFalse();
    });

    it('allows owner to delete their customer', function () {
        expect($this->owner->can('delete', $this->customer))->toBeTrue();
    });

    it('denies other user from deleting customer', function () {
        expect($this->otherUser->can('delete', $this->customer))->toBeFalse();
    });
});

// --- InvoiceNumberingSeriesPolicy ---

describe('InvoiceNumberingSeriesPolicy', function () {
    beforeEach(function () {
        $this->series = InvoiceNumberingSeries::create([
            'organization_id' => $this->organization->id,
            'name' => 'Test Series',
            'prefix' => 'TST',
            'format_pattern' => '{PREFIX}-{SEQUENCE:4}',
        ]);
    });

    it('allows owner to view their numbering series', function () {
        expect($this->owner->can('view', $this->series))->toBeTrue();
    });

    it('denies other user from viewing numbering series', function () {
        expect($this->otherUser->can('view', $this->series))->toBeFalse();
    });

    it('allows owner to update their numbering series', function () {
        expect($this->owner->can('update', $this->series))->toBeTrue();
    });

    it('denies other user from updating numbering series', function () {
        expect($this->otherUser->can('update', $this->series))->toBeFalse();
    });

    it('allows owner to delete their numbering series', function () {
        expect($this->owner->can('delete', $this->series))->toBeTrue();
    });

    it('denies other user from deleting numbering series', function () {
        expect($this->otherUser->can('delete', $this->series))->toBeFalse();
    });
});

// --- TaxTemplatePolicy ---

describe('TaxTemplatePolicy', function () {
    beforeEach(function () {
        $this->taxTemplate = TaxTemplate::create([
            'organization_id' => $this->organization->id,
            'name' => 'Test GST',
            'type' => 'GST',
            'rate' => 1800,
            'category' => 'goods',
            'country_code' => 'IN',
        ]);
    });

    it('allows owner to view their tax template', function () {
        expect($this->owner->can('view', $this->taxTemplate))->toBeTrue();
    });

    it('denies other user from viewing tax template', function () {
        expect($this->otherUser->can('view', $this->taxTemplate))->toBeFalse();
    });

    it('allows owner to update their tax template', function () {
        expect($this->owner->can('update', $this->taxTemplate))->toBeTrue();
    });

    it('denies other user from updating tax template', function () {
        expect($this->otherUser->can('update', $this->taxTemplate))->toBeFalse();
    });

    it('allows owner to delete their tax template', function () {
        expect($this->owner->can('delete', $this->taxTemplate))->toBeTrue();
    });

    it('denies other user from deleting tax template', function () {
        expect($this->otherUser->can('delete', $this->taxTemplate))->toBeFalse();
    });
});

// --- viewAny and create checks ---

describe('viewAny and create authorization', function () {
    it('allows authenticated user with team to viewAny and create for all models', function () {
        expect($this->owner->can('viewAny', Organization::class))->toBeTrue();
        expect($this->owner->can('viewAny', Customer::class))->toBeTrue();
        expect($this->owner->can('viewAny', InvoiceNumberingSeries::class))->toBeTrue();
        expect($this->owner->can('viewAny', TaxTemplate::class))->toBeTrue();

        expect($this->owner->can('create', Customer::class))->toBeTrue();
        expect($this->owner->can('create', InvoiceNumberingSeries::class))->toBeTrue();
        expect($this->owner->can('create', TaxTemplate::class))->toBeTrue();
    });
});
