<?php

namespace Tests\Feature;

use App\Models\Home;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The home screen renders for an authenticated user.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $home = Home::factory()->create();
        $user = User::factory()->create(['current_home_id' => $home->id]);
        $home->users()->attach($user, ['role' => 'owner']);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
    }
}
