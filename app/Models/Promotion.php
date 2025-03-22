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
    public function users()
    {
        return $this->belongsToMany(UserCredential::class, 'user_promotion', 'promotion_id', 'user_id');
    }
}
