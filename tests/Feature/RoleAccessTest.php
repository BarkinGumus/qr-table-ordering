<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Restaurant;
use App\Models\User;
use App\Tenancy\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/staff')->assertRedirect(route('login'));
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_every_restaurant_role_can_open_the_staff_screen(): void
    {
        foreach ([Role::Owner, Role::Manager, Role::Waiter, Role::Kitchen, Role::Bar] as $role) {
            $this->actingAs(User::factory()->role($role)->create())->get('/staff')->assertOk();
        }
    }

    public function test_a_platform_admin_has_no_staff_screen(): void
    {
        $this->actingAs(User::factory()->platformAdmin()->create())->get('/staff')->assertForbidden();
    }

    public function test_only_managing_roles_can_open_the_admin_panel(): void
    {
        foreach ([Role::Owner, Role::Manager] as $role) {
            $this->actingAs(User::factory()->role($role)->create())->get('/admin')->assertOk();
        }

        $this->actingAs(User::factory()->platformAdmin()->create())->get('/admin')->assertOk();

        foreach ([Role::Waiter, Role::Kitchen, Role::Bar] as $role) {
            $this->actingAs(User::factory()->role($role)->create())->get('/admin')->assertForbidden();
        }
    }

    public function test_an_owner_of_a_suspended_restaurant_is_locked_out_of_the_admin_panel(): void
    {
        $owner = User::factory()->role(Role::Owner)->for(Restaurant::factory()->suspended())->create();

        $this->actingAs($owner)->get('/admin')->assertForbidden();
    }

    public function test_a_request_is_tied_to_the_logged_in_users_restaurant(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/staff')->assertOk();

        $this->assertSame($user->restaurant_id, app(Tenant::class)->id());
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get('/login')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
