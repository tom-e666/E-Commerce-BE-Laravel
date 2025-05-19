<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
    ];
    protected $casts = [
        'price' => 'float',
        'options' => 'array',
        'product_id' => 'string',  // Important for MongoDB ID compatibility
    ];


    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    
    /**
     * Get product details from MongoDB
     */
    public function getProductDetailsAttribute()
    {
        // Get MongoDB product details
        if ($this->product) {
            return ProductDetail::where('product_id', (string)$this->product_id)->first();
        }
        return null;
    }
    
    /**
     * Get main product image
     */
    public function getImageAttribute()
    {
        $details = $this->getProductDetailsAttribute();
        if ($details && !empty($details->images)) {
            return $details->images[0];
        }
        return null;
    }
}
