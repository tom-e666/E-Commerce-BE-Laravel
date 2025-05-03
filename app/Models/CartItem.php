<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CartItem extends Model
{
    use HasFactory; 
    
    protected $table = 'cart_items';

    // Fillable fields
    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
    ];

    // Cast attributes
    protected $casts = [
        'user_id' => 'int',
        'product_id' => 'int',
        'quantity' => 'int',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
   
}