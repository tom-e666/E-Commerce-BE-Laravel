<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';
    
    public const VALID_STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_IN_PROGRESS,
        self::STATUS_RESOLVED,
        self::STATUS_CLOSED
    ];

    protected $fillable = [
        'user_id',
        'subject',
        'message',
        'status',
    ];
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($ticket) {
            if (!isset($ticket->status)) {
                $ticket->status = self::STATUS_OPEN;
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(UserCredential::class, 'user_id');
    }
    

    public function responses()
    {
        return $this->hasMany(SupportTicketResponse::class, 'ticket_id')
            ->orderBy('created_at', 'asc'); // Add ordering
    }
}