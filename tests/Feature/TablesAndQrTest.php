<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TableSessionStatus;
use App\Filament\Resources\DiningTables\Pages\CreateDiningTable;
use App\Filament\Resources\DiningTables\Pages\EditDiningTable;
use App\Filament\Resources\DiningTables\Pages\ListDiningTables;
use App\Models\DiningTable;
use App\Models\Restaurant;
use App\Models\TableSession;
use App\Models\User;
use App\Support\QrCode;
use App\Tenancy\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class TablesAndQrTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Restaurant $a;

    private Restaurant $b;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = app(Tenant::class);
        $this->a = Restaurant::factory()->create(['name' => 'Lokanta A']);
        $this->b = Restaurant::factory()->create(['name' => 'Lokanta B']);
        $this->owner = User::factory()->role(Role::Owner)->for($this->a)->create();
    }

    private function tableOf(Restaurant $restaurant, array $attributes = []): DiningTable
    {
        return $this->tenant->run($restaurant, fn () => DiningTable::factory()->create($attributes));
    }

    /** Filament's Livewire pages run outside the HTTP middleware in tests. */
    private function actingAsOwner(): void
    {
        $this->actingAs($this->owner);
        $this->tenant->set($this->a);
    }

    // ---- Admin screens -------------------------------------------------

    public function test_the_tables_page_lists_only_this_restaurants_tables(): void
    {
        $mine = $this->tableOf($this->a, ['label' => 'Bizim Masa']);
        $theirs = $this->tableOf($this->b, ['label' => 'Onların Masası']);

        $this->actingAs($this->owner)->get('/admin/dining-tables')
            ->assertOk()
            ->assertSee('Bizim Masa')
            ->assertDontSee('Onların Masası');

        $this->actingAsOwner();
        Livewire::test(ListDiningTables::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCountTableRecords(1);
    }

    public function test_an_owner_can_add_a_table_and_it_gets_a_token(): void
    {
        $this->actingAsOwner();

        Livewire::test(CreateDiningTable::class)
            ->fillForm(['label' => 'Teras 9', 'area' => 'Teras', 'capacity' => 6])
            ->call('create')
            ->assertHasNoFormErrors();

        $table = $this->tenant->run($this->a, fn () => DiningTable::where('label', 'Teras 9')->firstOrFail());

        $this->assertSame($this->a->id, $table->restaurant_id);
        $this->assertSame(22, strlen($table->qr_token));
    }

    public function test_a_label_must_be_unique_within_the_restaurant_but_not_across_restaurants(): void
    {
        $this->tableOf($this->a, ['label' => 'Masa 1']);
        $this->tableOf($this->b, ['label' => 'Masa 2']);
        $this->actingAsOwner();

        Livewire::test(CreateDiningTable::class)
            ->fillForm(['label' => 'Masa 1'])
            ->call('create')
            ->assertHasFormErrors(['label']);

        Livewire::test(CreateDiningTable::class)
            ->fillForm(['label' => 'Masa 2'])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_a_table_can_be_renamed_and_deactivated(): void
    {
        $table = $this->tableOf($this->a, ['label' => 'Eski']);
        $this->actingAsOwner();

        Livewire::test(EditDiningTable::class, ['record' => $table->getRouteKey()])
            ->fillForm(['label' => 'Yeni', 'is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $table->refresh();
        $this->assertSame('Yeni', $table->label);
        $this->assertFalse($table->is_active);
    }

    public function test_another_restaurants_table_cannot_be_opened_for_editing(): void
    {
        $theirs = $this->tableOf($this->b);

        $this->actingAs($this->owner)->get("/admin/dining-tables/{$theirs->id}/edit")->assertNotFound();
    }

    public function test_renewing_the_qr_from_the_list_changes_the_token(): void
    {
        $table = $this->tableOf($this->a);
        $old = $table->qr_token;
        $this->actingAsOwner();

        Livewire::test(ListDiningTables::class)->callTableAction('regenerateQr', $table);

        $this->assertNotSame($old, $table->fresh()->qr_token);
    }

    public function test_delete_is_offered_only_for_a_table_that_was_never_used(): void
    {
        $unused = $this->tableOf($this->a);
        $used = $this->tableOf($this->a);
        $this->tenant->run($this->a, fn () => TableSession::factory()->closed()->create(['dining_table_id' => $used->id]));
        $this->actingAsOwner();

        Livewire::test(ListDiningTables::class)
            ->assertTableActionVisible('delete', $unused)
            ->assertTableActionHidden('delete', $used)
            ->callTableAction('delete', $unused);

        $this->assertNull($this->tenant->run($this->a, fn () => DiningTable::find($unused->id)));
    }

    public function test_a_used_table_cannot_be_deleted_even_in_code(): void
    {
        $used = $this->tableOf($this->a);
        $this->tenant->run($this->a, fn () => TableSession::factory()->closed()->create(['dining_table_id' => $used->id]));

        $this->expectException(LogicException::class);
        $this->tenant->run($this->a, fn () => $used->delete());
    }

    public function test_waiters_cannot_reach_the_tables_page_and_the_platform_admin_does_not_see_it(): void
    {
        $waiter = User::factory()->role(Role::Waiter)->for($this->a)->create();
        $this->actingAs($waiter)->get('/admin/dining-tables')->assertForbidden();

        $platformAdmin = User::factory()->platformAdmin()->create();
        $this->actingAs($platformAdmin)->get('/admin/dining-tables')->assertForbidden();
        $this->actingAs($platformAdmin)->get('/admin')->assertOk();
    }

    // ---- Token rotation -------------------------------------------------

    public function test_renewing_a_qr_drops_an_unconfirmed_session_but_keeps_an_open_one(): void
    {
        $pendingTable = $this->tableOf($this->a);
        $openTable = $this->tableOf($this->a);

        $this->tenant->run($this->a, function () use ($pendingTable, $openTable) {
            $pending = TableSession::factory()->create(['dining_table_id' => $pendingTable->id]);
            $open = TableSession::factory()->open()->create(['dining_table_id' => $openTable->id]);

            $pendingTable->rotateToken();
            $openTable->rotateToken();

            $this->assertSame(TableSessionStatus::Expired, $pending->fresh()->status);
            $this->assertSame(TableSessionStatus::Open, $open->fresh()->status);
        });
    }

    // ---- Print sheet ----------------------------------------------------

    public function test_the_print_sheet_shows_a_qr_for_each_active_table_of_this_restaurant(): void
    {
        $this->tableOf($this->a, ['label' => 'S1']);
        $this->tableOf($this->a, ['label' => 'S2']);
        $this->tableOf($this->a, ['label' => 'Kapalı', 'is_active' => false]);
        $this->tableOf($this->b, ['label' => 'Yabancı']);

        $response = $this->actingAs($this->owner)->get('/print/qr-codes')->assertOk();

        $response->assertSee('Lokanta A')->assertSee('S1')->assertSee('S2')
            ->assertDontSee('Kapalı')->assertDontSee('Yabancı');
        $this->assertSame(2, substr_count($response->getContent(), '<svg'));
    }

    public function test_the_print_sheet_can_be_limited_to_chosen_tables_and_ignores_foreign_ids(): void
    {
        $one = $this->tableOf($this->a, ['label' => 'S1']);
        $this->tableOf($this->a, ['label' => 'S2']);
        $foreign = $this->tableOf($this->b, ['label' => 'Yabancı']);

        $this->actingAs($this->owner)->get("/print/qr-codes?tables={$one->id},{$foreign->id}")
            ->assertOk()->assertSee('S1')->assertDontSee('S2')->assertDontSee('Yabancı');

        $this->actingAs($this->owner)->get("/print/qr-codes?tables={$foreign->id}")->assertNotFound();
    }

    public function test_only_owners_and_managers_can_print_qr_codes(): void
    {
        $this->tableOf($this->a);

        $this->get('/print/qr-codes')->assertRedirect(route('login'));

        $this->actingAs(User::factory()->role(Role::Manager)->for($this->a)->create())->get('/print/qr-codes')->assertOk();
        $this->actingAs(User::factory()->role(Role::Waiter)->for($this->a)->create())->get('/print/qr-codes')->assertForbidden();
        $this->actingAs(User::factory()->platformAdmin()->create())->get('/print/qr-codes')->assertForbidden();
    }

    public function test_the_qr_encodes_exactly_the_tables_link(): void
    {
        $table = $this->tableOf($this->a);

        $this->assertSame(url('/t/'.$table->qr_token), $table->qrUrl());
        $this->assertStringNotContainsString((string) $this->a->id.'/', parse_url($table->qrUrl(), PHP_URL_PATH));
        $this->assertStringStartsWith('<svg', QrCode::svg($table->qrUrl()));
    }

    // ---- Scanning -------------------------------------------------------

    public function test_scanning_a_valid_code_shows_the_restaurant_and_table_without_login(): void
    {
        $table = $this->tableOf($this->a, ['label' => 'B4']);

        $this->get('/t/'.$table->qr_token)
            ->assertOk()
            ->assertSee('Lokanta A')
            ->assertSee('B4')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->assertSame($this->a->id, $this->tenant->id());
    }

    public function test_unknown_retired_inactive_and_suspended_codes_all_get_the_same_page(): void
    {
        $rotated = $this->tableOf($this->a);
        $oldToken = $rotated->qr_token;
        $this->tenant->run($this->a, fn () => $rotated->rotateToken());

        $inactive = $this->tableOf($this->a, ['is_active' => false]);

        $suspended = Restaurant::factory()->suspended()->create();
        $suspendedTable = $this->tableOf($suspended);

        $bodies = [];

        foreach (['AAAAAAAAAAAAAAAAAAAAAA', 'short', $oldToken, $inactive->qr_token, $suspendedTable->qr_token] as $token) {
            $response = $this->get('/t/'.$token)->assertNotFound()->assertSee(__('ui.guest.invalid_title'));
            $bodies[] = preg_replace('/name="csrf-token" content="[^"]*"/', '', $response->getContent());
        }

        $this->assertCount(1, array_unique($bodies), 'every invalid code must produce an identical page');

        // The renewed code works.
        $this->get('/t/'.$rotated->fresh()->qr_token)->assertOk();
    }

    public function test_scanning_is_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->get('/t/AAAAAAAAAAAAAAAAAAAAAA')->assertNotFound();
        }

        $this->get('/t/AAAAAAAAAAAAAAAAAAAAAA')->assertStatus(429);
    }
}
