<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Review extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'reviews';
    protected $fillable = [
        'product_id',
        'user_id',
        'rating',
        'comment',
    ];
    protected $casts = [
        'rating' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', '_id');
    }

    public function user()
    {
        return $this->belongsTo(UserCredential::class, 'user_id', 'id');
    }
    public function setProductIdAttribute($value)
    {
        $this->attributes['product_id'] = (string)$value;
    }
}
