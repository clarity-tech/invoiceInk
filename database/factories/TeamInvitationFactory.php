<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\TeamInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamInvitation>
 */
class TeamInvitationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TeamInvitation>
     */
    protected $model = TeamInvitation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Organization::factory(),
            'email' => $this->faker->unique()->safeEmail(),
            'role' => $this->faker->randomElement(['admin', 'editor', 'member']),
        ];
    }
}
