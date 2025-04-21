<?php

namespace App\Models;
use App\Models\UserCredential;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use Hasfactory;
    protected $fillable =[
        'name',
        'price',
        'stock',
        'status',
        'brand_id',
    ];
    protected $casts = [
        'price' => 'Float',
        'stock' => 'Integer',
        'status' => 'String',
    ];
    public function details()
    {
        return $this->hasOne(ProductDetail::class, 'product_id', 'id');
    }
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }
}
