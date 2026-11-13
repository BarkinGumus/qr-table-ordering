<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_login_page_follows_the_browser_language(): void
    {
        $this->get('/login', ['Accept-Language' => 'tr-TR,tr;q=0.9'])->assertOk()->assertSee('Personel girişi');
        $this->get('/login', ['Accept-Language' => 'en-GB,en;q=0.9'])->assertOk()->assertSee('Staff login');

        // A language we do not offer falls back to Turkish.
        $this->get('/login', ['Accept-Language' => 'de-DE,de;q=0.9'])->assertOk()->assertSee('Personel girişi');
    }

    public function test_a_visitor_can_switch_language_and_the_choice_sticks(): void
    {
        $this->get('/locale/en', ['Accept-Language' => 'tr'])->assertRedirect();
        $this->get('/login', ['Accept-Language' => 'tr'])->assertOk()->assertSee('Staff login');

        $this->get('/locale/xx')->assertNotFound();
    }

    public function test_a_waiter_logs_in_and_lands_on_the_staff_screen(): void
    {
        $user = User::factory()->role(Role::Waiter)->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('staff.home'));

        $this->assertAuthenticatedAs($user);
        $this->get('/staff')->assertOk()->assertSee($user->restaurant->name);
    }

    public function test_an_owner_lands_on_the_admin_panel(): void
    {
        $user = User::factory()->role(Role::Owner)->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/admin');
    }

    public function test_the_session_id_changes_at_login(): void
    {
        $user = User::factory()->create();

        $this->get('/login');
        $before = session()->getId();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertNotSame($before, session()->getId());
    }

    public function test_wrong_password_unknown_email_and_inactive_user_get_the_same_message(): void
    {
        $active = User::factory()->create();
        $inactive = User::factory()->inactive()->create();

        $attempts = [
            ['email' => $active->email, 'password' => 'wrong'],
            ['email' => 'nobody@example.com', 'password' => 'password'],
            ['email' => $inactive->email, 'password' => 'password'],
        ];

        foreach ($attempts as $attempt) {
            $this->from('/login')->post('/login', $attempt)
                ->assertRedirect('/login')
                ->assertSessionHasErrors(['email' => __('ui.login.failed')]);
            $this->assertGuest();
        }
    }

    public function test_staff_of_a_suspended_restaurant_cannot_log_in(): void
    {
        $user = User::factory()->for(Restaurant::factory()->suspended())->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_failures(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        // Even the right password is refused while the limit is active.
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_platform_admin_logs_in_on_the_same_page_and_lands_on_the_admin_panel(): void
    {
        $admin = User::factory()->platformAdmin()->create();

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect('/admin');

        $this->get('/admin')->assertOk();
    }

    public function test_one_ip_cannot_try_an_unlimited_number_of_emails(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->post('/login', ['email' => "user{$i}@example.com", 'password' => 'wrong'])->assertStatus(302);
        }

        $this->post('/login', ['email' => 'user21@example.com', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_logout_ends_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_a_user_deactivated_mid_session_is_signed_out_on_the_next_request(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/staff')->assertOk();

        $user->update(['is_active' => false]);

        $this->get('/staff')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
