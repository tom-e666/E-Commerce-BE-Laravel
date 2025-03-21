<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Order extends Model
{
    //
    use HasFactory;
    protected $fillable=[
        'order_id',
        'user_id',
        'total_price',
        'status',
        'created_at',
        'updated_at',
    ];
}
