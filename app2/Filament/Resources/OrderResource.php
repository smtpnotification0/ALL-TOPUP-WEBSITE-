<?php

namespace App\Filament\Resources;

use App\Constants\OrderStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\User; // User মডেল ইম্পোর্ট করা হয়েছে
use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Form;
use Illuminate\Support\Facades\DB; // DB ট্রানজ্যাকশনের জন্য ইম্পোর্ট করা হয়েছে
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification; // নোটিফিকেশনের জন্য ইম্পোর্ট করা হয়েছে
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function form(Form $form): Form
    {
        // User: নাম এবং আইডি সহ অপশনগুলো প্রস্তুত করা
        $userOptions = User::all()->mapWithKeys(function ($user) {
            return [$user->id => "{$user->name} (ID: {$user->id})"];
        })->toArray();

        return $form
            ->schema([
                Section::make('Order Details')
                    ->schema([
                        Hidden::make('id'),
                        Hidden::make('product_id'),
                        Hidden::make('variation_id'),

                        // 1. User ID সহ Name শো করা
                        Select::make('user_id')
                            ->label('User Name *')
                            ->options($userOptions)
                            ->searchable()
                            ->required()
                            ->columnSpanFull(),

                        // 2. Account Info (Editable) - ডেটাবেস কলাম 'account_info'
                        KeyValue::make('account_info') 
                            ->label('Account Info (Editable)') 
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->addable()
                            ->deletable()
                            ->columnSpanFull(), 

                        // 3. delivery_message
                        Textarea::make('delivery_message')
                            ->label('Delivery Message')
                            ->rows(3)
                            ->columnSpanFull(),

                        // 4. Product Title + Amount + Variation Title (Placeholder)
                        Placeholder::make('product_title')
                            ->label('Product Title')
                            ->content(fn($record) => "[#{$record->id}]** "
                                . ($record->product->title ?? 'N/A')
                                . ' | Amount: ৳'
                                . number_format($record->amount ?? 0, 2)
                                . ' | Variation: '
                                . ($record->variation->title ?? 'N/A')
                            ),

                        // 5. Player ID (Click to Copy & Open Link)
                        Placeholder::make('player_id_for_copy')
                            ->label('Player ID (Click to Copy & Logout)')
                            ->content(function (Order $record) {
                                $info = $record->account_info;
                                if (is_string($info)) {
                                    $info = json_decode($info, true);
                                }
                                return $info['player_id'] ?? 'Player ID Not Found';
                            })
                            // ✅ কাস্টম JavaScript দিয়ে In-line Copy ও লিঙ্ক ওপেন করা হলো
                            ->extraAttributes(function (Order $record) {
                                $info = $record->account_info;
                                if (is_string($info)) {
                                    $info = json_decode($info, true);
                                }
                                $playerId = $info['player_id'] ?? '';
                                
                                $garenaLogoutUrl = 'https://shop.garena.my/logout'; // আপনার লগআউট URL
                                
                                return [
                                    'class' => 'cursor-pointer p-2 bg-white border border-gray-300 rounded-lg shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white hover:border-primary-500 transition',
                                    'onclick' => "
                                        const playerId = '{$playerId}';
                                        if (playerId && playerId !== 'Player ID Not Found') {
                                            navigator.clipboard.writeText(playerId).then(() => { 
                                                // Filament Notification
                                                window.dispatchEvent(new CustomEvent('notification', { detail: { 
                                                    id: 'player_id_copy', 
                                                    title: 'Player ID copied!', 
                                                    status: 'success' 
                                                }}));
                                                // নতুন ট্যাবে গ্যারেনা লগআউট লিঙ্ক খোলা
                                                window.open('{$garenaLogoutUrl}', '_blank'); 
                                            });
                                        }
                                    ",
                                ];
                            })
                            ->columnSpanFull(),
                            
                        // 6. Status Select
                        Select::make('status')
                            ->options(OrderStatus::options())
                            ->required(),

                    ])
                    ->columns(2),
            ]);
    }

    //---------------------------------------------------------

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable()->searchable(),
                
                // User Name কলামের সার্চিং ফিক্স করা হয়েছে
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User Name (Searchable by Name/ID)')
                    ->description(fn (Order $record): string => "ID: {$record->user_id}")
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('user', function (Builder $q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                              ->orWhere('id', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('product.title')->label('Product')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('variation.title')->label('Variation')->sortable()->searchable(),

                // ✅ কলাম: Player ID - ইনলাইন কপি করা যাবে
                Tables\Columns\TextColumn::make('account_info_player_id')
                    ->label('Player ID')
                    ->getStateUsing(function (Order $record) {
                        $info = $record->account_info;
                        if (is_string($info)) {
                            $info = json_decode($info, true);
                        }
                        return $info['player_id'] ?? null;
                    })
                    ->copyable() 
                    ->copyMessage('Player ID copied!')
                    ->copyMessageDuration(1500)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->orWhere(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(account_info, '$.player_id'))"), 'like', "%{$search}%");
                    })
                    ->toggleable(isToggledHiddenByDefault: false) 
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')->label('Amount (৳)')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(fn(string $state) => match ($state) {
                        'pending' => 'warning',
                        'processing', 'auto-processing' => 'info',
                        'completed' => 'success',
                        'cancel' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Order Date')->dateTime()->sortable(),
                
                // 🔔 কলাম: delivery_message
                Tables\Columns\TextColumn::make('delivery_message')
                    ->label('Delivery Message')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(OrderStatus::options()),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->headerActions([
                // Header Actions ফাঁকা রাখা হলো
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                // 🚀 নতুন: ক্যানসেল অ্যাকশন (টাকা ফেরত সহ)
                Action::make('cancel_and_refund')
                    ->label('❌ Cancel & Refund')
                    ->color('danger')
                    ->icon('heroicon-m-currency-dollar')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Cancellation and Refund')
                    ->modalDescription(fn (Order $record) => 'Are you sure you want to cancel this order and refund ৳' . number_format($record->amount, 2) . ' to the user?')
                    ->action(function (Order $record) {
                        // DB ট্রানজ্যাকশনের মাধ্যমে টাকা ফেরত নিশ্চিত করা
                        DB::transaction(function () use ($record) {
                            // শুধুমাত্র Pending/Processing/Auto-processing স্ট্যাটাসেই ক্যানসেল করা যাবে
                            if (in_array($record->status, ['completed', 'cancel'])) {
                                Notification::make()
                                    ->title("Order #{$record->id} already {$record->status}.")
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $user = $record->user;
                            $refundAmount = $record->amount;

                            if ($user && $refundAmount > 0) {
                                // 1. User-এর ব্যালেন্স আপডেট
                                $user->increment('balance', $refundAmount);
                                
                                // 2. অর্ডারের স্ট্যাটাস 'cancel' এ পরিবর্তন
                                $record->status = 'cancel';
                                $record->save();

                                // 3. সফলতার নোটিফিকেশন
                                Notification::make()
                                    ->title("Order #{$record->id} Canceled & Refunded Successfully! ✅")
                                    ->body("৳" . number_format($refundAmount, 2) . " has been refunded to User: {$user->name} (ID: {$user->id}).")
                                    ->success()
                                    ->send();
                            } else {
                                // 4. যদি কোনো সমস্যা হয়
                                $record->status = 'cancel'; // টাকা ফেরত না গেলেও স্ট্যাটাস ক্যানসেল করে দেওয়া হলো
                                $record->save();
                                
                                Notification::make()
                                    ->title("Order #{$record->id} Canceled, but Refund Failed!")
                                    ->body("Refund amount was ৳0 or user not found. Only status changed to 'cancel'.")
                                    ->warning()
                                    ->send();
                            }
                        });
                    })
                    // Completed বা Canceled অর্ডারের জন্য বাটনটি লুকিয়ে রাখা
                    ->hidden(fn (Order $record): bool => $record->status === 'completed' || $record->status === 'cancel'),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    // Bulk Apply Delivery Message
                    Action::make('bulk_apply_message')
                        ->label('Apply Delivery Message to Selected')
                        ->color('primary')
                        ->icon('heroicon-m-envelope')
                        ->form([
                            Select::make('selected_message')
                                ->label('Select Message')
                                ->options(function () {
                                    // ⚠️ নিশ্চিত করুন 'delivery_message' নামে একটি টেবিল আছে এবং তাতে 'message' কলাম আছে।
                                    return DB::table('delivery_message')->pluck('message', 'message')->toArray();
                                })
                                ->searchable()
                                ->required(),
                        ])
                        ->requiresConfirmation()
                        ->action(function ($data, $records) {
                            $count = 0;
                            foreach ($records as $order) {
                                // DB::table ব্যবহার না করে Eloquent ব্যবহার করা হলো
                                $order->delivery_message = $data['selected_message'];
                                $order->save();
                                $count++;
                            }
                            Notification::make()->title("Delivery Message Applied to {$count} Orders ✅")->success()->send();
                        }),
                ]),
            ])
            // গ্লোবাল সার্চ লজিক: Player ID এবং Delivery Message সার্চ নিশ্চিত করা হয়েছে।
            ->modifyQueryUsing(function (Builder $query, string $search = '') {
                if (!empty($search)) {
                    $query->where(function (Builder $q) use ($search) {
                        // 1. Order ID, Amount
                        $q->orWhere('id', 'like', "%{$search}%")
                            ->orWhere('amount', 'like', "%{$search}%")

                            // 2. User Name এবং ID (Users টেবিল)
                            ->orWhereHas('user', function (Builder $q) use ($search) {
                                $q->where('name', 'like', "%{$search}%")
                                    ->orWhere('id', 'like', "%{$search}%");
                            })

                            // 3. Product Title এবং Variation Title
                            ->orWhereHas('product', function (Builder $q) use ($search) {
                                $q->where('title', 'like', "%{$search}%");
                            })
                            ->orWhereHas('variation', function (Builder $q) use ($search) {
                                $q->where('title', 'like', "%{$search}%");
                            })

                            // 4. Account Info JSON Search for 'player_id'
                            ->orWhere(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(account_info, '$.player_id'))"), 'like', "%{$search}%")

                            // 5. playerid_to (যদি থাকে)
                            ->orWhere('playerid_to', 'like', "%{$search}%")
                            
                            // 🔔 Delivery Message সার্চ
                            ->orWhere('delivery_message', 'like', "%{$search}%");
                    });
                }
            })
            ->paginated([10, 25, 50, 100, 200, 500, 1000]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrder::route('/'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    /**
     * 🔰 Navigation Badge দেখানোর জন্য
     */
    public static function getNavigationBadge(): ?string
    {
        $pending = Order::where('status', 'processing')->count();
        $auto    = Order::where('status', 'auto-processing')->count();

        // যদি কোনোটিই না থাকে, তবে ব্যাজ লুকিয়ে রাখা হলো
        if ($pending + $auto === 0) {
            return null;
        }

        return "🟨 Processing {$pending} | 🟦 AutoP {$auto}";
    }
}