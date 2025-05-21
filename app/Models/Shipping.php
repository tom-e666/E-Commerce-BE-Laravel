<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipping extends Model
{
    protected $fillable = [
        'order_id',
        'status', // 'pending', 'delivering', 'delivered', 'cancelled', 'returning', 'returned'
        'address',
        'recipient_name',
        'recipient_phone',
        'note',
        'ghn_order_code', // GHN order code
        'province_name',
        'district_name',
        'ward_name',
        'shipping_fee',
        'expected_delivery_time',
        'shipping_method', // SHOP, GHN
        'weight',
    ];
    protected $casts = [
        'expected_delivery_time' => 'datetime',
        'shipping_fee' => 'float',
    ];
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
