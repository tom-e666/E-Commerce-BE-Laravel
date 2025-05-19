<?php

namespace App\Models;
use App\Models\ProductDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Brand;
use App\Models\Review;

class Product extends Model
{
    use HasFactory;
    
    protected $fillable = [
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

    /**
     * Get product details from MongoDB
     */
    public function getDetailsAttribute()
    {
        return ProductDetail::where('product_id', (string)$this->id)->first();
    }

    /**
     * Get MySQL relationship
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
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
}
