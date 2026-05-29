<?php

use App\Http\Controllers\DashboardController;

it('uses DATE_FORMAT for mysql and mariadb', function () {
    expect(DashboardController::monthKeyExpression('mysql'))
        ->toBe("DATE_FORMAT(issued_at, '%Y-%m')")
        ->and(DashboardController::monthKeyExpression('mariadb'))
        ->toBe("DATE_FORMAT(issued_at, '%Y-%m')");
});

it('uses strftime for sqlite', function () {
    expect(DashboardController::monthKeyExpression('sqlite'))
        ->toBe("strftime('%Y-%m', issued_at)");
});

it('uses to_char for postgres', function () {
    expect(DashboardController::monthKeyExpression('pgsql'))
        ->toBe("to_char(issued_at, 'YYYY-MM')");
});

it('never emits to_char on mysql (regression for missing to_char function)', function () {
    expect(DashboardController::monthKeyExpression('mysql'))
        ->not->toContain('to_char');
});
