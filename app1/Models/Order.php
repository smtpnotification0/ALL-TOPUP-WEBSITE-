<?php

namespace App\Models;

use App\Constants\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'product_id',
        'variation_id',
        'amount',
        'profit',
        'delivery_message',
        'account_info',
        'provider_data',
        'track_id',
        'quantity',
        'attempts',
        'status',
        'claimed',
    ];

    protected $casts = [
        'attempts'      => 'boolean',
        'account_info'  => 'array',
        'provider_data' => 'array',
        'claimed'       => 'boolean',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(Variation::class);
    }

    public function voucher(): HasOne
    {
        return $this->hasOne(Voucher::class);
    }

    /**
     * Cancel order and refund user
     */
    public function cancel(): bool
    {
        if (!in_array($this->status, [OrderStatus::PROCESSING, OrderStatus::AUTOPROCESSING])) {
            return false;
        }

        $user = $this->user;
        if ($user) {
            $user->balance += $this->amount;
            $user->save();
        }

        $this->status = OrderStatus::CANCEL;
        $this->save();

        return true;
    }

    /**
     * Boot method to handle referral coins and variation coins
     */
    protected static function boot()
    {
        parent::boot();

        static::updated(function ($order) {

            if ($order->status === OrderStatus::COMPLETE && !$order->claimed) {

                $user = $order->user;

                /** -------------------------------
                 * ✅ ১⃣ Variation gift_coins যোগ (User gets coins)
                 * -------------------------------*/
                if ($order->variation && $user) {
                    $variationCoins = (int) ($order->variation->gift_coins ?? 0);
                    $totalCoinsToAdd = $variationCoins * ($order->quantity ?? 1);

                    if ($totalCoinsToAdd > 0) {
                        $user->addCoins($totalCoinsToAdd);
                    }
                }

                /** ---------------------------------------
                 * ✅ ২⃣ Referral coins (Referrer gets same coins as user)
                 * ---------------------------------------*/
                $referrer = $order->user->referrer ?? null;

                if ($referrer && $order->variation) {
                    $variationCoins = (int) ($order->variation->gift_coins ?? 0);
                    $totalCoinsForReferrer = $variationCoins * ($order->quantity ?? 1);

                    if ($totalCoinsForReferrer > 0) {
                        $referrer->addCoins($totalCoinsForReferrer);
                    }
                }

                /** -----------------------------
                 * ✅ Repeat না হওয়ার জন্য claimed
                 * -----------------------------*/
                $order->claimed = true;
                $order->save();
            }
        });
    }
}