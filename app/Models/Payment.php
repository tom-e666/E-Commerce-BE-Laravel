<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    //
    protected $fillable = [
        'order_id',
        'payment_method',
        'payment_status',
        'transaction_id',
    ];
    protected $casts = [
        'order_id'=>'String',
        'payment_method'=>'String',
        'payment_status'=>'String',
        'transaction_id'=>'String',
    ];
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
    
}
