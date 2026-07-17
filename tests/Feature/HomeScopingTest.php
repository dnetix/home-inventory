<?php

namespace Tests\Feature;

use App\Exceptions\MissingCurrentHomeException;
use App\Models\Home;
use App\Models\Item;
use App\Models\User;
use App\Support\CurrentHome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeScopingTest extends TestCase
{
    use RefreshDatabase;

    private function userWithHome(): array
    {
        $home = Home::factory()->create();
        $user = User::factory()->create(['current_home_id' => $home->id]);
        $home->users()->attach($user, ['role' => 'owner']);

        return [$user, $home];
    }

    public function test_queries_only_see_the_current_users_home(): void
    {
        [$user, $home] = $this->userWithHome();
        [, $otherHome] = $this->userWithHome();

        Item::factory()->for($home)->create(['name' => 'My drill']);
        Item::factory()->for($otherHome)->create(['name' => 'Their drill']);

        $this->actingAs($user);

        $this->assertSame(['My drill'], Item::pluck('name')->all());
    }

    public function test_created_models_are_stamped_with_the_current_home(): void
    {
        [$user, $home] = $this->userWithHome();

        $this->actingAs($user);

        $item = Item::create(['name' => 'New thing']);

        $this->assertSame($home->id, $item->home_id);
    }

    public function test_queries_without_a_resolvable_home_fail_loudly(): void
    {
        $this->expectException(MissingCurrentHomeException::class);

        Item::count();
    }

    public function test_for_home_scope_bypasses_the_global_scope_explicitly(): void
    {
        [, $home] = $this->userWithHome();

        Item::factory()->for($home)->count(2)->create();

        $this->assertSame(2, Item::forHome($home)->count());
    }

    public function test_current_home_override_allows_unauthenticated_contexts(): void
    {
        [, $home] = $this->userWithHome();

        Item::factory()->for($home)->create();

        app(CurrentHome::class)->override($home);

        $this->assertSame(1, Item::count());
    }
}
