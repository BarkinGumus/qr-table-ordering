<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Tenancy\MissingTenantException;
use App\Tenancy\Tenant;
use App\Tenancy\TenantMismatchException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\Note;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Restaurant $a;

    private Restaurant $b;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id');
            $table->string('body');
        });

        $this->tenant = app(Tenant::class);
        $this->a = Restaurant::factory()->create();
        $this->b = Restaurant::factory()->create();

        $this->tenant->run($this->a, fn () => Note::create(['body' => 'from A']));
        $this->tenant->run($this->b, fn () => Note::create(['body' => 'from B']));
    }

    public function test_queries_only_return_the_current_restaurants_rows(): void
    {
        $bodies = $this->tenant->run($this->a, fn () => Note::pluck('body')->all());

        $this->assertSame(['from A'], $bodies);
    }

    public function test_another_restaurants_row_cannot_be_found_by_id(): void
    {
        $idOfB = $this->tenant->run($this->b, fn () => Note::firstOrFail()->id);

        $found = $this->tenant->run($this->a, fn () => Note::find($idOfB));

        $this->assertNull($found);
    }

    public function test_another_restaurants_row_cannot_be_updated_or_deleted_in_bulk(): void
    {
        $this->tenant->run($this->a, function () {
            Note::query()->update(['body' => 'overwritten']);
            Note::query()->delete();
        });

        $this->assertSame(['from B'], $this->tenant->run($this->b, fn () => Note::pluck('body')->all()));
    }

    public function test_new_rows_get_the_current_restaurant_automatically(): void
    {
        $note = $this->tenant->run($this->a, fn () => Note::create(['body' => 'second']));

        $this->assertSame($this->a->id, $note->restaurant_id);
    }

    public function test_a_row_cannot_be_created_for_another_restaurant(): void
    {
        $this->expectException(TenantMismatchException::class);

        $this->tenant->run($this->a, fn () => Note::create(['body' => 'x', 'restaurant_id' => $this->b->id]));
    }

    public function test_a_row_cannot_be_moved_to_another_restaurant(): void
    {
        $this->expectException(TenantMismatchException::class);

        $this->tenant->run($this->a, function () {
            $note = Note::firstOrFail();
            $note->restaurant_id = $this->b->id;
            $note->save();
        });
    }

    public function test_querying_without_a_restaurant_fails_loudly(): void
    {
        $this->expectException(MissingTenantException::class);

        Note::count();
    }

    public function test_creating_without_a_restaurant_fails_loudly(): void
    {
        $this->expectException(MissingTenantException::class);

        Note::create(['body' => 'orphan']);
    }

    public function test_platform_code_can_opt_out_explicitly(): void
    {
        $count = $this->tenant->withoutScope(fn () => Note::count());

        $this->assertSame(2, $count);
        $this->assertFalse($this->tenant->bypassed());
    }

    public function test_run_restores_the_previous_restaurant(): void
    {
        $this->tenant->set($this->a);

        $this->tenant->run($this->b, fn () => null);

        $this->assertSame($this->a->id, $this->tenant->id());
    }
}
