<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ProductDetail extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'product_details';
    protected $fillable = [
        'product_id',
        'description',
        'images',
        'keywords',
        'specifications',
    ];
    protected $casts = [
        'images' => 'array',
        'keywords' => 'array',
        'specifications' => 'array',
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
     * Get recent reviews manually instead of through relationship
     */
    public function getRecentReviewsAttribute($amount = 5)
    {
        return Review::where('product_id', $this->product_id)
            ->orderBy('created_at', 'desc')
            ->limit($amount)
            ->get();
    }
    
    /**
     * Convert IDs to strings before storing in MongoDB
     */
    public function setProductIdAttribute($value)
    {
        $this->attributes['product_id'] = (string)$value;
    }
}
