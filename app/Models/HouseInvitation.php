<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HouseInvitation extends Model
{
    protected $fillable = [
        'house_id',
        'invited_by',
        'invited_email',
        'token',
        'status',
    ];

    // status: pending | accepted | rejected | expired

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->token = Str::uuid();
        });
    }

    public function house()
    {
        return $this->belongsTo(House::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
