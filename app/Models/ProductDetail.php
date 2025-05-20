<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\HybridRelations;
use App\Models\Product;

class ProductDetail extends Model
{
    use HybridRelations;
    
    protected $connection = 'mongodb';
    // Keep collection name consistent with your actual MongoDB collection
    protected $collection = 'product_details';
    
    protected $fillable = [
        'product_id',
        'description',
        'images',
        'keywords',
        'specifications',
    ];
    
    protected $casts = [
        'product_id' => 'integer', // Cast product_id as integer consistently
        'images' => 'array',
        'keywords' => 'array',
        'specifications' => 'array',
    ];
    
    /**
     * Always set product_id as integer when saving to MongoDB
     */
    public function setProductIdAttribute($value)
    {
        $this->attributes['product_id'] = (int)$value;
    }
    
    /**
     * Ensure product_id is returned as integer
     */
    public function getProductIdAttribute($value)
    {
        return (int)$value;
    }
    
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
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
}
