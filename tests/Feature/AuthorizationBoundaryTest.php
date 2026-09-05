<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_access_another_users_project(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $other = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::create(['owner_id' => $owner->id, 'name' => 'Owner Project', 'status' => 'draft', 'current_step' => 0]);

        $this->actingAs($other)->get(route('projects.show', $project))->assertForbidden();
    }
}
