<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Review extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'reviews';
    protected $primaryKey = '_id';
    protected $fillable = [
        'product_id',
        'user_id',
        'rating',
        'comment',
        'order_item_id',
    ];
    protected $casts = [
        'rating' => 'integer',
    ];

    /**
     * Get product data through an accessor instead of a relationship
     * This avoids cross-database relationship issues
     */
    public function getProductAttribute()
    {
        return Product::find($this->product_id);
    }

    /**
     * Get user data through an accessor instead of a relationship
     * This avoids cross-database relationship issues
     */
    public function getUserAttribute()
    {
        return UserCredential::find($this->user_id);
    }
    
    /**
     * Convert IDs to strings before storing in MongoDB
     */
    public function setProductIdAttribute($value)
    {
        $this->attributes['product_id'] = (string)$value;
    }
    
    public function setUserIdAttribute($value)
    {
        $this->attributes['user_id'] = (string)$value;
    }
    
    public function setOrderItemIdAttribute($value)
    {
        $this->attributes['order_item_id'] = (string)$value;
    }

    public function product(){
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
    public function user(){
        return $this->belongsTo(UserCredential::class, 'user_id', 'id');
    }
}
