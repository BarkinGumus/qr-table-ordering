<?php

namespace App\Filament\Resources\DiningTables\Tables;

use App\Models\DiningTable;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class DiningTablesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withExists(['liveSession', 'sessions']))
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('label')
                    ->label(__('ui.tables.label'))
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('area')
                    ->label(__('ui.tables.area'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('capacity')
                    ->label(__('ui.tables.capacity'))
                    ->sortable(),
                TextColumn::make('live_session_exists')
                    ->label(__('ui.tables.status'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? __('ui.tables.in_use') : __('ui.tables.free'))
                    ->color(fn (bool $state) => $state ? 'warning' : 'gray'),
                IconColumn::make('is_active')
                    ->label(__('ui.tables.active'))
                    ->boolean(),
                TextColumn::make('qr_rotated_at')
                    ->label(__('ui.tables.qr_rotated_at'))
                    ->since()
                    ->placeholder(__('ui.tables.never')),
            ])
            ->recordActions([
                Action::make('printQr')
                    ->label(__('ui.tables.print_qr'))
                    ->icon('heroicon-o-printer')
                    ->url(fn (DiningTable $record) => route('print.qr-codes', ['tables' => $record->id]))
                    ->openUrlInNewTab(),
                // Less frequent actions sit behind the row's menu button.
                ActionGroup::make([
                    Action::make('regenerateQr')
                        ->label(__('ui.tables.regenerate'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading(__('ui.tables.regenerate_heading'))
                        ->modalDescription(__('ui.tables.regenerate_body'))
                        ->action(function (DiningTable $record): void {
                            $record->rotateToken();

                            Notification::make()->title(__('ui.tables.regenerated'))->success()->send();
                        }),
                    EditAction::make(),
                    // Deleting a table would erase its sessions and orders, so it
                    // is only offered while the table has never been used.
                    DeleteAction::make()
                        ->visible(fn (DiningTable $record) => ! $record->sessions_exists),
                ]),
            ])
            ->toolbarActions([
                BulkAction::make('printSelected')
                    ->label(__('ui.tables.print_selected'))
                    ->icon('heroicon-o-printer')
                    ->action(fn (Collection $records) => redirect()->route('print.qr-codes', [
                        'tables' => $records->modelKeys() ? implode(',', $records->modelKeys()) : null,
                    ])),
            ]);
    }
}
