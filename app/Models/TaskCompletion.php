<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskCompletion extends Model
{
    protected $fillable = [
        'task_id',
        'house_id',
        'user_id',
        'completed_at',
        'photo_url',
        'points_earned',
        'completion_date',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'completion_date' => 'date',
        'points_earned' => 'integer',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function house()
    {
        return $this->belongsTo(House::class);
    }
}
