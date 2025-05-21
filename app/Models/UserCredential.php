<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class UserCredential extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    const ROLE_ADMIN ='admin';
    const ROLE_USER ='user';
    const ROLE_STAFF ='staff';
    
    protected $table = 'user_credentials';

    protected $fillable = [
        'id',
        'email',
        'phone',
        'password',
        'full_name',
        'role',
        'email_verified',
        'email_verification_token',
        'email_verification_sent_at',
        // 'phone_verified',
    ];

    protected $hidden = [
        'password',
        'role'
    ];

    protected $casts = [
        'email_verified' => 'boolean',
    ];

    /**
     * Set the user's password.
     *
     * @param  string  $value
     * @return void
     */
    // public function setPasswordAttribute($value): void
    // {
    //     if (Hash::needsRehash($value)) {
    //         $this->attributes['password'] = Hash::make($value);
    //     } else {
    //         $this->attributes['password'] = $value;
    //     }
    // }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class, 'user_id', 'id');
    }
    /**
     * Check if user has admin role
     * 
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
    
    /**
     * Check if user has staff role
     * 
     * @return bool
     */
    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }
}
