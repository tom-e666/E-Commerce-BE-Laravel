<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use Hasfactory;
    protected $fillable =[
        'name',
        'description',
        'price',
        'stock',
        'status',
        'created_at',
    ];
    protected $casts = [
        'price' => 'float',
        'stock' => 'integer',
        'status' => 'boolean',
    ];
    public function details()
    {
        return $this->hasOne(ProductDetail::class, 'product_id', 'id');
    }
}
