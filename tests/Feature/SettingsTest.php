<?php

namespace Tests\Feature;

use App\Enums\Theme;
use App\Enums\Unit;
use App\Livewire\Account;
use App\Livewire\Settings;
use App\Models\Home;
use App\Models\Item;
use App\Models\User;
use App\Support\Dimensions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private Home $home;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->home = Home::factory()->create();
        $this->user = User::factory()->create(['current_home_id' => $this->home->id]);
        $this->home->users()->attach($this->user, ['role' => 'owner']);
        $this->actingAs($this->user);
    }

    public function test_switching_units_persists_and_updates_the_sample(): void
    {
        Livewire::test(Settings::class)
            ->assertSee('25 × 22 × 9 cm')
            ->call('setUnit', 'imperial')
            ->assertSee('9.8 × 8.7 × 3.5 in');

        $this->assertSame(Unit::Imperial, $this->user->fresh()->unit);
    }

    public function test_imperial_users_see_converted_sizes_on_item_screens(): void
    {
        $this->user->update(['unit' => Unit::Imperial]);
        $item = Item::factory()->for($this->home)->withDimensions(new Dimensions(250, 220, 90))->create();

        $this->get(route('items.show', $item))->assertSee('9.8 × 8.7 × 3.5 in');
    }

    public function test_theme_choice_persists(): void
    {
        Livewire::test(Settings::class)
            ->call('setTheme', 'dark')
            ->assertDispatched('theme-changed', theme: 'dark');

        $this->assertSame(Theme::Dark, $this->user->fresh()->theme);
    }

    public function test_notifications_toggle_persists(): void
    {
        Livewire::test(Settings::class)
            ->set('notifications', false);

        $this->assertFalse($this->user->fresh()->notifications);
    }

    public function test_account_profile_can_be_updated(): void
    {
        Livewire::test(Account::class)
            ->set('name', 'Diego C.')
            ->set('email', 'diego@example.com')
            ->call('save')
            ->assertHasNoErrors();

        $this->user->refresh();
        $this->assertSame('Diego C.', $this->user->name);
        $this->assertSame('diego@example.com', $this->user->email);
    }

    public function test_account_rejects_taken_emails(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        Livewire::test(Account::class)
            ->set('email', 'taken@example.com')
            ->call('save')
            ->assertHasErrors(['email']);
    }

    public function test_password_can_be_changed(): void
    {
        Livewire::test(Account::class)
            ->set('currentPassword', 'password')
            ->set('password', 'new-secret-123')
            ->set('passwordConfirmation', 'new-secret-123')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertSet('currentPassword', '')
            ->assertSet('password', '')
            ->assertSet('passwordConfirmation', '');

        $this->assertTrue(Hash::check('new-secret-123', $this->user->fresh()->password));
    }

    public function test_password_change_rejects_a_wrong_current_password(): void
    {
        Livewire::test(Account::class)
            ->set('currentPassword', 'not-my-password')
            ->set('password', 'new-secret-123')
            ->set('passwordConfirmation', 'new-secret-123')
            ->call('updatePassword')
            ->assertHasErrors(['currentPassword']);

        $this->assertTrue(Hash::check('password', $this->user->fresh()->password));
    }

    public function test_password_change_requires_a_matching_confirmation(): void
    {
        Livewire::test(Account::class)
            ->set('currentPassword', 'password')
            ->set('password', 'new-secret-123')
            ->set('passwordConfirmation', 'different-thing')
            ->call('updatePassword')
            ->assertHasErrors(['password']);
    }

    public function test_more_menu_renders_with_counts(): void
    {
        $this->get(route('more'))
            ->assertOk()
            ->assertSee('Categories')
            ->assertSee('Settings')
            ->assertSee($this->user->email);
    }
}
