<?php

namespace App\Filament\Resources\DiningTables\Pages;

use App\Filament\Resources\DiningTables\DiningTableResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDiningTables extends ListRecords
{
    protected static string $resource = DiningTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('printAll')
                ->label(__('ui.tables.print_all'))
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(route('print.qr-codes'))
                ->openUrlInNewTab(),
            CreateAction::make(),
        ];
    }
}
