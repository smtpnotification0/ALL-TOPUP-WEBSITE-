<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Constants\TopupProvider;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Filters\QueryBuilder\Constraints\NumberConstraint;

class VariationsRelationManager extends RelationManager
{
    protected static string $relationship = 'variations';

    /**
     * Define the relationship between price change and gift_coins change.
     * COIN_RATIO = 1.0 means 1 unit price change = 1 unit gift_coins change (1:1 relationship).
     * Adjust this value based on your business logic.
     */
    protected const COIN_RATIO = 1.0; 

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                TextInput::make('buy_rate')
                    ->label('Buy Rate')
                    ->required()
                    ->numeric()
                    ->inputMode('decimal'),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->inputMode('decimal'),
                TextInput::make('gift_coins')
                    ->required()
                    ->numeric()
                    ->inputMode('decimal'),
                TextInput::make('stock')
                    ->required()
                    ->integer()
                    ->inputMode('decimal'),
                Toggle::make('automatic')
                    ->label('Auto TopUP')
                    ->visible(fn(): bool => gs()->enable_auto_topup)
                    ->live()
                    ->columnSpanFull(),
                Select::make('provider')
                    ->label('Auto Topup Provider')
                    ->visible(fn(): bool => gs()->enable_auto_topup)
                    ->options(TopupProvider::OPTIONS)
                    ->required()
                    ->live()
                    ->hidden(fn(Get $get): bool => !$get('automatic')),
                Select::make('provider_product_id')
                    ->label('Provider Product')
                    ->visible(fn(): bool => gs()->enable_auto_topup)
                    ->options(TopupProvider::PRODUCTVARIATIONS)
                    ->required()
                    ->hidden(fn(Get $get): bool => !$get('automatic') || !$get('provider')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('buy_rate')
                    ->label('Buy Rate')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('gift_coins')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('price')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('stock')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                QueryBuilder::make()
                    ->constraints([
                        NumberConstraint::make('price'),
                        NumberConstraint::make('stock'),
                    ]),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Bulk Action to increase Price and Gift Coins (1:1 Ratio)
                    Tables\Actions\BulkAction::make('increasePrice')
                        ->label('Increase Price')
                        ->icon('heroicon-o-arrow-up')
                        ->color('success')
                        ->form([
                            TextInput::make('amount')
                                ->label('Increase by (Amount)')
                                ->numeric()
                                ->required()
                                ->default(1),
                        ])
                        ->action(function (array $data, $records) {
                            $amount = (float) $data['amount'];
                            
                            foreach ($records as $record) {
                                // 1. Update Price
                                $record->price += $amount;
                                if ($record->price < 0) {
                                    $record->price = 0;
                                }

                                // 2. Update Gift Coins (1:1 with price change)
                                $record->gift_coins += $amount; // Using $amount directly
                                if ($record->gift_coins < 0) {
                                    $record->gift_coins = 0;
                                }
                                
                                $record->save();
                            }
                        })
                        ->requiresConfirmation(),

                    // Bulk Action to decrease Price and Gift Coins (1:1 Ratio)
                    Tables\Actions\BulkAction::make('decreasePrice')
                        ->label('Decrease Price')
                        ->icon('heroicon-o-arrow-down')
                        ->color('danger')
                        ->form([
                            TextInput::make('amount')
                                ->label('Decrease by (Amount)')
                                ->numeric()
                                ->required()
                                ->default(1),
                        ])
                        ->action(function (array $data, $records) {
                            $amount = (float) $data['amount'];
                            
                            foreach ($records as $record) {
                                // 1. Update Price
                                $record->price -= $amount;
                                if ($record->price < 0) {
                                    $record->price = 0; // Ensure price doesn't go negative
                                }

                                // 2. Update Gift Coins (1:1 with price change)
                                $record->gift_coins -= $amount; // Using $amount directly
                                if ($record->gift_coins < 0) {
                                    $record->gift_coins = 0; // Ensure coins don't go negative
                                }

                                $record->save();
                            }
                        })
                        ->requiresConfirmation(),

                    // Existing Delete Bulk Action
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->deferLoading()
            ->paginated([10, 25, 50, 100, 200, 500, 1000]);
    }
}