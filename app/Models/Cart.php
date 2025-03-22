<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
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
        'total',
    ];
    // Cast attributes
    protected $casts = [
        'product_id' => 'array',
        'quantity' => 'array',
        'price' => 'array',
        'total' => 'float', // Use 'float' instead of 'number'
    ];

    /**
     * Relationship: Cart belongs to a User
     */
    public function user()
    {
        return $this->belongsTo(UserCredential::class, 'user_id', 'id');
    }

    /**
     * Relationship: Cart has many Products
     */
    public function products()
    {
        return Product::whereIn('_id', $this->product_id)->get();
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Handle creating, updating, and deleting events
        static::creating(function ($cart) {
            $cart->onChange();
        });

        static::updating(function ($cart) {
            $cart->onChange();
        });

        static::deleting(function ($cart) {
            $cart->onChange();
        });
    }

    /**
     * Calculate the total price of the cart
     */
    public function onChange()
    {
        $this->total = 0;

        $products = Product::whereIn('_id', $this->product_id)->get()->keyBy('_id');

        foreach ($this->product_id as $index => $productId) {
            $product = $products->get($productId);

            if ($product === null) {

                $this->quantity[$index] = 0;
            } else {

                $this->total += $this->quantity[$index] * $product->price;
            }
        }
    }
}