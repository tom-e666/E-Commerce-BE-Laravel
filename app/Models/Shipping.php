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
        'status',//packed, shipped, delivered,cancelled
    ];
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
