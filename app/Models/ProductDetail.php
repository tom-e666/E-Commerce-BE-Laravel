<?php

namespace App\Models;

use Jenssegers\Database\Eloquent\Model;

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
        'images' => 'array',
        'keywords' => 'array'
    ];
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
