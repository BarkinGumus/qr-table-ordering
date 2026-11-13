<?php

namespace App\Http\Controllers;

use App\Models\DiningTable;
use App\Support\QrCode;
use App\Tenancy\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A printable sheet of QR codes: all active tables, or the ones listed in
 * ?tables=1,2,3. Table IDs are looked up through the restaurant-filtered
 * model, so another restaurant's IDs simply match nothing.
 */
class PrintQrCodesController extends Controller
{
    public function __invoke(Request $request, Tenant $tenant): View
    {
        $ids = collect(explode(',', (string) $request->query('tables')))
            ->filter(fn ($id) => ctype_digit($id))
            ->map(fn ($id) => (int) $id);

        $tables = DiningTable::query()
            ->when($ids->isNotEmpty(), fn ($query) => $query->whereKey($ids), fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        abort_if($tables->isEmpty(), 404);

        return view('print.qr-codes', [
            'restaurant' => $tenant->get(),
            'cards' => $tables->map(fn (DiningTable $table) => [
                'label' => $table->label,
                'area' => $table->area,
                'svg' => QrCode::svg($table->qrUrl()),
            ]),
        ]);
    }
}
