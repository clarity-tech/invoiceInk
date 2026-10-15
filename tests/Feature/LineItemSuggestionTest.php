<?php

use App\Ai\Agents\LineItemClassifier;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Prompts\AgentPrompt;

beforeEach(function () {
    config(['ai.providers.anthropic.key' => 'test-key']);
});

function suggestingOrganization(array $attributes = []): Organization
{
    return createOrganizationWithLocation(array_merge(['ai_drafting_enabled' => true], $attributes));
}

function postSuggestion(User $user, string $description = 'Monthly IT support retainer'): TestResponse
{
    return test()->actingAs($user)->postJson('/invoices/item-suggestion', ['description' => $description]);
}

it('suggests the SAC code and tax rate for a line', function () {
    $organization = suggestingOrganization();
    LineItemClassifier::fake([['code' => '9983 14', 'tax_rate_percent' => 18, 'reason' => 'An IT support service.']]);

    postSuggestion($organization->owner)
        ->assertOk()
        ->assertExactJson(['sac_code' => '998314', 'tax_rate' => 1800, 'reason' => 'An IT support service.']);

    LineItemClassifier::assertPrompted(fn (AgentPrompt $prompt) => $prompt->contains('Line: Monthly IT support retainer'));
});

it('drops what it cannot stand behind', function (array $output) {
    LineItemClassifier::fake([array_merge(['reason' => 'Unclear.'], $output)]);

    postSuggestion(suggestingOrganization()->owner)
        ->assertOk()
        ->assertJson(['sac_code' => null, 'tax_rate' => null]);
})->with([
    'a code that is not a number' => [['code' => 'SAC-998314', 'tax_rate_percent' => null]],
    'a rate the organization does not charge' => [['code' => null, 'tax_rate_percent' => 12]],
]);

it('asks for no code outside India', function () {
    $organization = suggestingOrganization(['country_code' => 'AE', 'currency' => 'AED']);
    LineItemClassifier::fake([['code' => null, 'tax_rate_percent' => 5, 'reason' => 'A service.']]);

    postSuggestion($organization->owner)->assertOk()->assertJson(['tax_rate' => 500]);

    LineItemClassifier::assertPrompted(fn (AgentPrompt $prompt) => str_contains((string) $prompt->agent->instructions(), 'the business is not in India'));
});

it('needs a description to go on', function () {
    LineItemClassifier::fake()->preventStrayPrompts();

    postSuggestion(suggestingOrganization()->owner, 'ab')->assertJsonValidationErrors('description');
});

it('is offered only to organizations that opted in', function () {
    LineItemClassifier::fake()->preventStrayPrompts();

    postSuggestion(suggestingOrganization(['ai_drafting_enabled' => false])->owner)->assertForbidden();
});

it('refuses a member who cannot create invoices', function () {
    $organization = suggestingOrganization();
    $viewer = User::factory()->create();
    $organization->users()->attach($viewer, ['role' => 'viewer']);
    $viewer->switchTeam($organization);
    LineItemClassifier::fake()->preventStrayPrompts();

    postSuggestion($viewer)->assertForbidden();
});

it('says so when there is no suggestion', function (Closure|array $reply) {
    LineItemClassifier::fake($reply instanceof Closure ? $reply : [$reply]);

    postSuggestion(suggestingOrganization()->owner)
        ->assertJsonValidationErrors(['suggestion' => 'No suggestion just now. Try again, or fill it in yourself.']);
})->with([
    'the provider is down' => [fn () => fn () => throw new ProviderOverloadedException('Overloaded')],
    'the reply was cut short' => [['code' => null, 'tax_rate_percent' => null]],
]);
