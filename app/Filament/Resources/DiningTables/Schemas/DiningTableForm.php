<?php

namespace App\Filament\Resources\DiningTables\Schemas;

use App\Tenancy\Tenant;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class DiningTableForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->label(__('ui.tables.label'))
                    ->helperText(__('ui.tables.label_help'))
                    ->required()
                    ->maxLength(30)
                    // Unique within this restaurant only; another venue may
                    // also have a "Masa 1".
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule) => $rule->where('restaurant_id', app(Tenant::class)->id()),
                    ),
                TextInput::make('area')
                    ->label(__('ui.tables.area'))
                    ->maxLength(50),
                TextInput::make('capacity')
                    ->label(__('ui.tables.capacity'))
                    ->integer()
                    ->minValue(1)
                    ->maxValue(99),
                TextInput::make('sort_order')
                    ->label(__('ui.tables.sort_order'))
                    ->integer()
                    ->minValue(0)
                    ->maxValue(9999)
                    ->default(0)
                    ->required(),
                Toggle::make('is_active')
                    ->label(__('ui.tables.active'))
                    ->helperText(__('ui.tables.active_help'))
                    ->default(true),
            ]);
    }
}
