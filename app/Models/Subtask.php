<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subtask extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'subject',
        'start_date',
        'due_date',
        'priority',
        'project_id',
        'assignees',
        'task_description',
        'status',
        'attach_file',
    ];

    protected $casts = [
        'assignees' => 'array', 
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
