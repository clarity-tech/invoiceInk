<?php

use App\Actions\Jetstream\CreateTeam;

it('redirectTo returns organization setup route', function () {
    $action = new CreateTeam;

    expect($action->redirectTo())->toBe(route('organization.setup'));
});
