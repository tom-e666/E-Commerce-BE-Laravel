<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicketResponse extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'ticket_id',
        'user_id',
        'subject',
        'message',
    ];
    
    /**
     * Get the ticket this response belongs to
     */
    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }
    
    /**
     * Get the user who created the response
     */
    public function user()
    {
        return $this->belongsTo(UserCredential::class, 'user_id');
    }
}