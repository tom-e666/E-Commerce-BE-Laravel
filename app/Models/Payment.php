<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    //
    $fillable = [
        'order_id',
        'payment_method',
        'payment_status',
        'transaction_id',
        'created_at',
    ];
}
