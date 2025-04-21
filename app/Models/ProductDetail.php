<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ProductDetail extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'product_detail';
    protected $fillable = [
        'product_id',
        'description',
        'images',
        'keywords',
        'specifications',
    ];
    protected $casts = [
        'specifications' => 'array',
        'images' => 'string',
        'keywords' => 'string'
    ];
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
    public function reviews()
    {
        return $this->hasMany(Review::class, 'product_detail_id', 'id');
    }
    public function recentReviews($amount = 5)
    {
        return $this->reviews()
            ->orderBy('created_at', 'desc')
            ->limit($amount)
            ->get();
    }

}
