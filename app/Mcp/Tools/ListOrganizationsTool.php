<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesActingOrganization;
use App\Models\Organization;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Description(
    'List the organizations the authenticated user belongs to, and which one is currently active. '
    .'Every other tool reads only the active organization, so call this first when a question names '
    .'a specific business, to confirm whose data you are about to report.'
)]
#[Name('list-organizations')]
class ListOrganizationsTool extends Tool
{
    use ResolvesActingOrganization;

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $this->actingUser($request);

        if ($user instanceof Response) {
            return $user;
        }

        $current = $user->currentTeam;

        // Organization carries no global organization scope, so it must be
        // reached through the user's own memberships — never a bare query.
        $organizations = $user->allTeams()
            ->map(fn (Organization $organization): array => [
                'id' => $organization->id,
                'name' => $organization->name,
                'company_name' => $organization->company_name,
                'currency' => $organization->currency?->value,
                'country_code' => $organization->country_code,
                'is_current' => $organization->id === $current?->id,
                'setup_complete' => $organization->setup_completed_at !== null,
            ])
            ->values()
            ->all();

        return Response::structured([
            'current_organization_id' => $current?->id,
            'organizations' => $organizations,
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
