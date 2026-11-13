<?php

namespace Tests\Feature;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\Station;
use App\Enums\TableSessionStatus;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\SessionGuest;
use App\Models\TableSession;
use App\Tenancy\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Restaurant $a;

    private Restaurant $b;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = app(Tenant::class);
        $this->a = Restaurant::factory()->create();
        $this->b = Restaurant::factory()->create();
    }

    /** Runs the callback as restaurant A. */
    private function asA(callable $callback): mixed
    {
        return $this->tenant->run($this->a, $callback(...));
    }

    private function asB(callable $callback): mixed
    {
        return $this->tenant->run($this->b, $callback(...));
    }

    public function test_a_new_table_gets_a_random_22_character_url_safe_token(): void
    {
        [$first, $second] = $this->asA(fn () => [DiningTable::factory()->create(), DiningTable::factory()->create()]);

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]{22}$/', $first->qr_token);
        $this->assertNotSame($first->qr_token, $second->qr_token);
    }

    public function test_the_token_cannot_be_set_through_mass_assignment(): void
    {
        $table = $this->asA(fn () => DiningTable::create(['label' => 'X1', 'qr_token' => 'chosen-by-an-attacker']));

        $this->assertNotSame('chosen-by-an-attacker', $table->qr_token);
    }

    public function test_rotating_a_token_replaces_it_and_records_when(): void
    {
        $table = $this->asA(fn () => DiningTable::factory()->create());
        $old = $table->qr_token;

        $this->asA(fn () => $table->rotateToken());

        $this->assertNotSame($old, $table->fresh()->qr_token);
        $this->assertNotNull($table->fresh()->qr_rotated_at);
    }

    public function test_two_restaurants_can_both_have_a_table_with_the_same_label(): void
    {
        $this->asA(fn () => DiningTable::create(['label' => 'Masa 1']));
        $this->asB(fn () => DiningTable::create(['label' => 'Masa 1']));

        $this->expectException(UniqueConstraintViolationException::class);
        $this->asA(fn () => DiningTable::create(['label' => 'Masa 1']));
    }

    public function test_a_table_can_have_only_one_live_session(): void
    {
        $table = $this->asA(fn () => DiningTable::factory()->create());
        $this->asA(fn () => TableSession::create(['dining_table_id' => $table->id]));

        $this->expectException(UniqueConstraintViolationException::class);
        $this->asA(fn () => TableSession::create(['dining_table_id' => $table->id]));
    }

    public function test_closing_a_session_frees_the_table_for_the_next_party(): void
    {
        $table = $this->asA(fn () => DiningTable::factory()->create());

        $this->asA(function () use ($table) {
            $first = TableSession::create(['dining_table_id' => $table->id]);
            $this->assertTrue($table->liveSession->is($first));

            $first->update(['status' => TableSessionStatus::Closed, 'closed_at' => now()]);

            $second = TableSession::create(['dining_table_id' => $table->id, 'status' => TableSessionStatus::Open]);

            $this->assertTrue($table->fresh()->liveSession->is($second));
            $this->assertCount(2, $table->sessions);
        });
    }

    public function test_an_expired_session_also_frees_the_table(): void
    {
        $this->asA(function () {
            $session = TableSession::factory()->create();
            $session->update(['status' => TableSessionStatus::Expired]);

            $this->assertNull($session->fresh()->live_table_id);
        });
    }

    public function test_a_bulk_update_that_skips_the_model_still_frees_the_tables(): void
    {
        $this->asA(function () {
            $table = DiningTable::factory()->create();
            TableSession::create(['dining_table_id' => $table->id]);

            // The way an expiry job would do it: no model events fire.
            TableSession::query()->update(['status' => TableSessionStatus::Expired->value]);

            $this->assertNull($table->liveSession);
            $this->assertNotNull(TableSession::create(['dining_table_id' => $table->id])->id);
        });
    }

    public function test_a_bulk_update_cannot_reopen_two_sessions_on_one_table(): void
    {
        $this->expectException(UniqueConstraintViolationException::class);

        $this->asA(function () {
            $table = DiningTable::factory()->create();
            TableSession::factory()->closed()->count(2)->create(['dining_table_id' => $table->id]);

            TableSession::query()->update(['status' => TableSessionStatus::Open->value]);
        });
    }

    public function test_hard_deleting_a_category_keeps_the_order_lines_of_its_products(): void
    {
        $this->asA(function () {
            $product = Product::factory()->create();
            $order = Order::factory()->create();
            $item = OrderItem::create([
                'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'Kola',
                'unit_price' => 25, 'quantity' => 2, 'station' => Station::Bar,
            ]);

            $product->category->delete();

            $item->refresh();
            $this->assertNull($item->product_id);
            $this->assertSame('Kola', $item->product_name);
            $this->assertSame('25.00', $item->unit_price);
        });
    }

    public function test_the_same_order_cannot_be_submitted_twice(): void
    {
        $key = (string) Str::uuid();
        $this->asA(fn () => Order::factory()->create(['idempotency_key' => $key]));

        // Another restaurant may happen to receive the same key.
        $this->asB(fn () => Order::factory()->create(['idempotency_key' => $key]));

        $this->expectException(UniqueConstraintViolationException::class);
        $this->asA(fn () => Order::factory()->create(['idempotency_key' => $key]));
    }

    public function test_an_order_line_keeps_its_name_and_price_after_the_product_changes_or_is_deleted(): void
    {
        $this->asA(function () {
            $product = Product::factory()->create(['name' => ['tr' => 'Kola', 'en' => 'Cola'], 'price' => 25]);
            $order = Order::factory()->create();

            $item = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->translate('name', 'tr'),
                'unit_price' => $product->price,
                'quantity' => 2,
                'station' => Station::Bar,
            ]);

            $product->update(['price' => 40, 'name' => ['tr' => 'Kola Zero']]);
            $product->delete();

            $item->refresh();
            $this->assertSame('Kola', $item->product_name);
            $this->assertSame('25.00', $item->unit_price);
            $this->assertSame(OrderItemStatus::Received, $item->status);
            $this->assertNotNull($item->product, 'a soft-deleted product is still reachable from old order lines');
            $this->assertNull(Product::find($product->id));
        });
    }

    public function test_translations_fall_back_sensibly(): void
    {
        $this->asA(function () {
            $both = Category::factory()->create(['name' => ['tr' => 'İçecekler', 'en' => 'Drinks']]);
            $turkishOnly = Category::factory()->create(['name' => ['tr' => 'Tatlılar']]);

            app()->setLocale('en');
            $this->assertSame('Drinks', $both->translate('name'));
            $this->assertSame('Tatlılar', $turkishOnly->translate('name'));

            app()->setLocale('tr');
            $this->assertSame('İçecekler', $both->translate('name'));
        });
    }

    public function test_enums_are_cast(): void
    {
        $this->asA(function () {
            $category = Category::factory()->bar()->create();
            $order = Order::factory()->create(['status' => OrderStatus::AwaitingConfirmation]);
            $payment = Payment::create([
                'table_session_id' => $order->table_session_id,
                'method' => PaymentMethod::CardTerminal,
                'amount' => 120.5,
            ]);

            $this->assertSame(Station::Bar, $category->fresh()->station);
            $this->assertSame(OrderStatus::AwaitingConfirmation, $order->fresh()->status);
            $this->assertSame(TableSessionStatus::Open, $order->session->status);
            $this->assertSame(PaymentMethod::CardTerminal, $payment->fresh()->method);
            $this->assertSame('120.50', $payment->fresh()->amount);
        });
    }

    public function test_a_guest_key_is_stored_only_as_a_hash(): void
    {
        $this->asA(function () {
            $session = TableSession::factory()->create();
            $plain = Str::random(40);

            $guest = SessionGuest::create(['table_session_id' => $session->id, 'key_hash' => SessionGuest::hashKey($plain)]);

            $this->assertNotSame($plain, $guest->key_hash);
            $this->assertSame(64, strlen($guest->key_hash));
            $this->assertArrayNotHasKey('key_hash', $guest->toArray());
        });
    }

    public function test_every_restaurant_owned_model_is_invisible_to_another_restaurant(): void
    {
        $this->asA(function () {
            $product = Product::factory()->create();
            $order = Order::factory()->create();
            OrderItem::create([
                'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'x',
                'unit_price' => 1, 'quantity' => 1, 'station' => Station::Kitchen,
            ]);
            SessionGuest::create(['table_session_id' => $order->table_session_id, 'key_hash' => SessionGuest::hashKey('k')]);
            Payment::create(['table_session_id' => $order->table_session_id, 'method' => PaymentMethod::Cash, 'amount' => 1]);
        });

        $models = [
            DiningTable::class, TableSession::class, SessionGuest::class, Category::class,
            Product::class, Order::class, OrderItem::class, Payment::class,
        ];

        foreach ($models as $model) {
            $this->assertSame(1, $this->asA(fn () => $model::count()), "{$model} should have one row for restaurant A");
            $this->assertSame(0, $this->asB(fn () => $model::count()), "{$model} leaked to restaurant B");
        }
    }

    public function test_a_table_cannot_be_found_by_another_restaurants_token_lookup(): void
    {
        $token = $this->asA(fn () => DiningTable::factory()->create()->qr_token);

        $this->assertNull($this->asB(fn () => DiningTable::where('qr_token', $token)->first()));
    }

    public function test_the_demo_seeder_builds_a_restaurant_with_tables_and_a_bilingual_menu_and_can_run_twice(): void
    {
        $this->seed();
        $this->seed();

        $demo = Restaurant::where('slug', 'demo-lokanta')->firstOrFail();

        $this->tenant->run($demo, function () {
            $this->assertSame(12, DiningTable::count());
            $this->assertSame(5, Category::count());
            $this->assertSame(18, Product::count());
            $this->assertSame(4, Product::whereRelation('category', 'station', Station::Bar->value)->count());
            $this->assertSame('Fresh Orange Juice', Product::where('name->tr', 'Taze Sıkma Portakal Suyu')->firstOrFail()->translate('name', 'en'));
        });
    }

    public function test_deleting_a_restaurant_that_has_orders_with_items_removes_its_data(): void
    {
        $this->asA(function () {
            $product = Product::factory()->create();
            $order = Order::factory()->create();
            OrderItem::create([
                'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'x',
                'unit_price' => 1, 'quantity' => 1, 'station' => Station::Kitchen,
            ]);
            Payment::create(['table_session_id' => $order->table_session_id, 'method' => PaymentMethod::Cash, 'amount' => 1]);
        });

        $this->a->delete();

        $left = $this->tenant->withoutScope(fn () => Order::count() + OrderItem::count() + TableSession::count()
            + DiningTable::count() + Product::withTrashed()->count() + Category::count() + Payment::count());

        $this->assertSame(0, $left);
    }

    public function test_an_order_cannot_point_at_a_missing_session(): void
    {
        $this->expectException(QueryException::class);

        $this->asA(fn () => Order::factory()->create(['table_session_id' => 999999, 'dining_table_id' => 999999]));
    }
}
