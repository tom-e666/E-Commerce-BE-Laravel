<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    //
    protected $fillable = [
        'order_id',
        'payment_method',
        'payment_status',// // 'pending', 'completed', 'failed'
        'transaction_id',
        'amount',
        'payment_time',

    ];
    protected $casts = [

    ];
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
