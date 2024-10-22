<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskTimer extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'started_at',
        'stopped_at',
        'total_hours',
        'assignees', // Include this field
    ];

    protected $casts = [
        'assignees' => 'array',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    // public function employee()
    // {
    //     return $this->belongsTo(User::class);
    // }
}
