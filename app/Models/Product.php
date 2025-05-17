<?php

namespace App\Models;
use App\Models\UserCredential;
use App\Models\ProductDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;
    
    protected $fillable =[
        'name',
        'price',
        'stock',
        'status',
        'brand_id',
        'weight'
    ];
    protected $casts = [
        'price' => 'float',
        'stock' => 'integer',
        'status' => 'boolean',
    ];

    protected $appends = ['details'];

    public function getDetailsAttribute()
    {
        return ProductDetail::where('product_id', $this->id)->first();
    }

    public function details()
    {
        return $this->hasOne(ProductDetail::class, 'product_id', 'id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }

    public function labelDetail()
    {
        $details = $this->details;
        
        if (!$details) {
            return [
                'product_id' => $this->id,
                'images' => null,
                'price' => $this->price,
                'stock' => $this->stock,
                'status' => $this->status,
            ];
        }
        
        return [
            'product_id' => $this->id,
            'images' => $details->images && count($details->images) > 0 ? $details->images[0] : null,
            'price' => $this->price,
            'stock' => $this->stock,
            'status' => $this->status,
        ];
    }
}
