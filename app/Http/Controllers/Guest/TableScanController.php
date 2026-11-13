<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Tenancy\Tenant;
use Illuminate\Http\Response;

/**
 * Where a scanned QR code lands. The token is the only input; it selects
 * the table and, through it, the restaurant.
 *
 * For now this only shows which table was scanned. Step 5 replaces the
 * page with the menu and starts the table session here.
 */
class TableScanController extends Controller
{
    public function __invoke(string $token, Tenant $tenant): Response
    {
        // The one place a restaurant-owned row is looked up without a
        // restaurant: the token itself is what identifies the restaurant.
        $table = $tenant->withoutScope(fn () => DiningTable::query()
            ->with('restaurant')
            ->where('qr_token', $token)
            ->where('is_active', true)
            ->first());

        // Unknown, retired and switched-off codes all get the same page, so
        // the response never reveals which tokens exist.
        if ($table === null || ! $table->restaurant->isActive()) {
            return $this->page('guest.invalid-code', [], 404);
        }

        $tenant->set($table->restaurant);

        return $this->page('guest.table', ['table' => $table, 'restaurant' => $table->restaurant]);
    }

    private function page(string $view, array $data, int $status = 200): Response
    {
        return response()
            ->view($view, $data, $status)
            // The address contains the token: keep it out of caches, search
            // engines and the Referer header of any outgoing link.
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
