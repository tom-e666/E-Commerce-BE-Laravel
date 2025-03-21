<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class OrderItem extends Model
{
    //
    use HasFactory;
    protected $fillable=[
        'order_id',
        'product_id',
        'quantity',
        'price',
        'created_at',
        'updated_at',
    ];
    protected static function boot()
    {
        parent::boot();
        static::creating(function($orderItem){
            $orderItem->onCreate();
        });
        static::updating(function($orderItem){
            $orderItem->onUpdate();
        });
        static::deleting(function($orderItem){
            $orderItem->onDelete();
        });
    }
    public function onCreate()
    {
        $order=Order::find($this->order_id);
        if($order===null){
            return;
        }
        $order->total_price+=$this->price;
    }
    public function onUpdate()
    {
        $order=Order::find($this->order_id);
        if($order===null){
            return;
        }
        $order->total_price-=$this->getOriginal('price')*$this->getOriginal('quantity');
        $order->total_price+=$this->price*$this->quantity;
    }
    public function onDelete()
    {
        $order=Order::find($this->order_id);
        if($order===null){
            return;
        }
        $order->total_price-=$this->price*$this->quantity;
    }
}
