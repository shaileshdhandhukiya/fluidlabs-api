<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'start_date',
        'due_date',
        'priority',
        'project_id',
        'assignees',
        'task_description',
        'status',
        'attach_file',
        'estimation_hours'
    ];

    protected $casts = [
        'assignees' => 'array', // Store assignees as array
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    
}
