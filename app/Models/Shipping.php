<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipping extends Model
{
    protected $fillable = [
        'order_id',
        'tracking_code',
        'carrier',//GHN,GRAB,SHOP
        'estimated_date',
        'status',//pending-> packed-> shipped, delivered,cancelled,
        'address',
        'recipient_name',
        'recipient_phone',
        'note'
    ];
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
