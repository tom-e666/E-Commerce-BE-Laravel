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
        'description',
        'price',
        'stock',
        'status',
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
}
