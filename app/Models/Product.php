<?php

namespace App\Models;
use App\Models\ProductDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Brand;
use App\Models\Review;
use MongoDB\Laravel\Eloquent\HybridRelations; 
class Product extends Model
{
    use HasFactory,HybridRelations;
    
    protected $fillable = [
        'name',
        'price',
        'stock',
        'status',
        'brand_id',
        'weight',
        'category_id'
    ];
    protected $casts = [
        'price' => 'float',
        'stock' => 'integer',
        'status' => 'boolean',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    public function details()
    {
        return $this->hasOne(ProductDetail::class, 'product_id', 'id');
    }
    /**
     * Get the primary image for the product
     */
    public function getImageAttribute()
    {
        $details = $this->details;
        return ($details && !empty($details->images)) ? $details->images[0] : null;
    }
    
    /**
     * Format product details for cart/order display
     */
    public function labelDetail()
    {
        $details = $this->details;
        
        return [
            'product_id' => (string)$this->id,
            'images' => ($details && !empty($details->images)) ? $details->images[0] : null,
            'price' => (float)$this->price,
            'stock' => (int)$this->stock,
            'status' => (bool)$this->status,
        ];
    }
    public function image()
    {
        return $this->details->images[0] ?? null;
    }
}
