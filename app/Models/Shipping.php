<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipping extends Model
{
    protected $fillable = [
        'order_id',
        'tracking_code',
        'carrier',//fedex, dhl, ups
        'estimated_date',
        'status',//received//packed, shipped, delivered,cancelled,
        'address'
    ];
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
