<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Order extends Model
{
    //
    use HasFactory;
    protected $fillable=[
        'order_id',
        'user_id',
        'total_price',
        'status',
    ];
    public function user()
    {
        return $this->belongsTo(UseCredential::class, 'user_id', 'id');
    }
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }
    public function payment()
    {
        return $this->hasOne(Payment::class, 'order_id', 'id');
    }
    protected static function boot()
    {
        parent::boot();
        static::creating(function($order){
            $order->onChange();
        });
        static::updating(function($order){
            $order->onChange();
        });
        static::deleting(function($order){
            $order->onChange();
        });
    }
    protected function onChange()
    {
        
    }
}
