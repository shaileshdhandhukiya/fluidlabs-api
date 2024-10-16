<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_name',
        'customer_id',
        'status',
        'progress',
        'members',
        'estimated_hours',
        'start_date',
        'deadline',
        'description',
        'project_files',
        'send_project_created_email'
    ];

    protected $casts = [
        'members' => 'array', // Store members as array
        'project_files' => 'array',
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

    // Update the status enum to reflect the new 'delivered' value
    const STATUS = [
        'NOT_STARTED' => 'not started',
        'IN_PROGRESS' => 'in progress',
        'ON_HOLD' => 'on hold',
        'CANCELLED' => 'cancelled',
        'DELIVERED' => 'delivered',  // Changed from 'finished' to 'delivered'
    ];
}
