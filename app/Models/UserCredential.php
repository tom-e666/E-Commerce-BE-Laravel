<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tymon\JWTAuth\Contracts\JWTSubject;

class UserCredential extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'user_credentials';

    protected $fillable = [
        'username',
        'email',
        'phone',
        'password',
        'full_name',
        'role',
        // 'email_verified',
        // 'phone_verified',
    ];

    protected $hidden = [
        'password',
        'role'
    ];

    protected $casts = [
        // 'email_verified' => 'boolean',
        // 'phone_verified' => 'boolean',
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
}
