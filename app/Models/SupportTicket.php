<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    //
    protected $fillable = [
        'user_id',
        'subject',
        'message',
        'status',
    ];
    public function response(){
        return $this->hasMany(SupportTicketResponse::class, 'ticket_id', 'id');
    }
}
