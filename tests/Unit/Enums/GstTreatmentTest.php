<?php

use App\Enums\GstTreatment;

it('exposes domestic and overseas options', function () {
    expect(GstTreatment::options())->toBe([
        'domestic' => 'Domestic',
        'overseas' => 'Overseas',
    ]);
});

it('resolves from its stored value', function () {
    expect(GstTreatment::from('overseas'))->toBe(GstTreatment::Overseas);
    expect(GstTreatment::Overseas->label())->toBe('Overseas');
});
