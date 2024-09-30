<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_name', 'customer_id', 'status', 'progress', 'members', 'estimated_hours', 'start_date', 'deadline', 'description', 'send_project_created_email'
    ];

    protected $casts = [
        'members' => 'array', // Store members as array
        'send_project_created_email' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
