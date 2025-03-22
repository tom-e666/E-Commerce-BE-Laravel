<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class OrderItem extends Model
{
    use HasFactory;
    protected $fillable=[
        'order_id',
        'product_id',
        'quantity',
        'price',
        'total',
        'promo_code',
        'created_at',
        'updated_at',
    ];
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'promo_code', 'code');
    }
    protected static function boot()
    {
        parent::boot();
        static::creating(function($orderItem){
            $orderItem->onChange();
        });
        static::updating(function($orderItem){
            $orderItem->onChange();
        });
        static::deleting(function($orderItem){
            $orderItem->onChange();
        });
    }
    public function onChange()
    {   
        $this->price=$this->product->price;
        $this->quantity=min($this->quantity,$this->product->stock);
        $total=$this->price*$this->quantity;
        if($this->promo_code)
        {
            $this->total=$this->promotion->applyPromotion($this);
        }
        else
        {
            $this->total=$total;
        }
    }
}
