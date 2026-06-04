<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'user_gmail',
        'method',
        'transaction_id',
        'amount',
        'page',
        'order_id',
        'sender_number',
        'receiver_number',
        'status',
        'time_paid',
        'unpaid',
        'note',
        'webhook_response',
    ];

    protected $casts = [
        'webhook_response' => 'array',
        'time_paid'        => 'datetime',
        'unpaid'           => 'boolean',
        'amount'           => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
