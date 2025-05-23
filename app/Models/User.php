<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use HasFactory, Notifiable;

    const ROLE_USER = 'user';
    const ROLE_RETAILER = 'retailer';
    const ROLE_ADMIN = 'admin';

    const STATUS_ACTIVE = 'active';
    const STATUS_PENDING = 'pending';
    const STATUS_BLOCKED = 'blocked';

    protected $fillable = [
        'name',
        'phone_number',
        'email',
        'address',
        'password',
        'role',
        'status',
        'business_name',
        'tax_id',
        'profile_picture',
        'business_registration_image',
        'email_verification_code',
        'email_verified_at'
    ];

    protected $hidden = [
        'password',
        'email_verification_code',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    protected function getJwtTtl(): int
{
    $ttl = config('jwt.ttl', 1440);
    return is_string($ttl) ? (int) $ttl : $ttl;
}

public function hasVerifiedEmail()
{
    return $this->email_verified_at !== null;
}

public function getJWTCustomClaims()
{
    return [
        'role' => $this->role,
        'status' => $this->status,
        'email_verified' => $this->hasVerifiedEmail(),
        'exp' => now()->addMinutes($this->getJwtTtl())->timestamp
    ];
}


    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isRetailer()
    {
        return $this->role === self::ROLE_RETAILER;
    }

    public function isUser()
    {
        return $this->role === self::ROLE_USER;
    }

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isBlocked()
    {
        return $this->status === self::STATUS_BLOCKED;
    }


public function shops()
{
    return $this->hasMany(Shop::class, 'user_id');
}
public function wishlist()
{
    return $this->hasMany(Wishlist::class);
}
public function contacts()
{
    return $this->hasMany(ContactMessage::class);
}
}


