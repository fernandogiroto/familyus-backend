<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class House extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'created_by',
        'status',
        'game_start_date',
        'game_end_date',
        'tasks_locked',
    ];

    protected $casts = [
        'game_start_date' => 'datetime',
        'game_end_date' => 'datetime',
        'tasks_locked' => 'boolean',
    ];

    // status: setup | waiting_ready | active | finished
    public function members()
    {
        return $this->belongsToMany(User::class, 'house_members')
            ->withPivot('is_ready', 'score')
            ->withTimestamps();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function invitations()
    {
        return $this->hasMany(HouseInvitation::class);
    }

    public function taskCompletions()
    {
        return $this->hasMany(TaskCompletion::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
