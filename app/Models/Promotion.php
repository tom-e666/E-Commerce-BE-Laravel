<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    //
    protected $fillable=[
        'code',
        'name',
        'discount_rule',
        'limit',
        'start_date',
        'end_date',
        'status',
        'created_at',
        'updated_at',
        'user_rule',
        'product_id'
    ];
    protected $casts = [
        'discount_rule' => 'string',
        'user_rule' => 'string',
    ];
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_promotion', 'promotion_id', 'product_id');
    }
    public function isValid($orderItem)
    {
        //find a way to make this later
        return true;
    }
    public function applyPromotion($orderItem){
           if($this->isValid($orderItem)){
            //temporary discount
               $orderItem->total = $orderItem->total - 100000;
           }
           return $orderItem->total;
    }
}
