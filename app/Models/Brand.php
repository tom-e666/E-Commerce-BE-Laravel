<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model{

    // table name
    protected $table = 'brands';

    protected $fillable =[
        'name',
    ];

    protected $casts = [
        'name' => 'string',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'id', 'brand_id');
    }
}