<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    // Use MongoDB connection
    protected $connection = 'mongodb';
    protected $collection = 'cart';

    // Fillable fields
    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'price',
        'total'
    ];
    // Cast attributes
    protected $casts = [
        'user_id' => 'String',
        'product_id' => 'String',
        'quantity' => 'Int',
        'price' => 'Float',
        'total' => 'Float', // Use 'float' instead of 'number'
    ];
    /**
     * Relationship: Cart belongs to a User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', '_id');
    }
    /**
     * Relationship: Cart belongs to a Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', '_id');
    }
   
}