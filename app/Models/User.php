<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'fcm_token',
        'avatar_color',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function houses()
    {
        return $this->belongsToMany(House::class, 'house_members')
            ->withPivot('is_ready', 'score')
            ->withTimestamps();
    }

    public function taskCompletions()
    {
        return $this->hasMany(TaskCompletion::class);
    }

    public function sentInvitations()
    {
        return $this->hasMany(HouseInvitation::class, 'invited_by');
    }
}
