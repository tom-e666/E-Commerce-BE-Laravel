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
    ];
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
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
            $promotion=Promotion::where('code',$this->promo_code)->first();
            if($promotion 
            && $promotion->status==='open'
            && $promotion->min_price<=$total
            && $promotion->start_date<=now()
            && $promotion->end_date>=now()
            && $promotion->users()->where('user_id',$this->order->user_id)->exists())
            {
                if($promotion->discount_type==='percentage')
                {
                    $total=$total-($total*$promotion->discount_value/100);
                }
                else if($promotion->discount_type==='fixed')
                {
                    $total=$total-$promotion->discount_value;
                }
                {
                    $total=$total-$promotion->discount_value;
                }    
            }
        }
        $this->total=$total;
        
            
            
    }
}
