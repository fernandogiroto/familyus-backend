<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'house_id',
        'name',
        'frequency',
        'points',
        'day_of_week',
        'day_of_month',
        'created_by',
        'emoji',
    ];

    protected $casts = [
        'points' => 'integer',
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
    ];

    // frequency: daily | weekly | monthly

    public function house()
    {
        return $this->belongsTo(House::class);
    }

    public function completions()
    {
        return $this->hasMany(TaskCompletion::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isScheduledFor(\Carbon\Carbon $date): bool
    {
        return match ($this->frequency) {
            'daily' => true,
            'weekly' => $this->day_of_week === $date->dayOfWeek,
            'monthly' => $this->day_of_month === $date->day,
            default => false,
        };
    }
}
