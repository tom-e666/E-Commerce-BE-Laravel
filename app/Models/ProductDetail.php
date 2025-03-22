<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductDetail extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'product_detail';
    protected $fillable = [
        'product_id',
        'name',
        'description',
        'images',
        'keywords',
        'specifications',
        'updated_at',
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
