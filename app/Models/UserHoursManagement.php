<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserHoursManagement extends Model
{
    use HasFactory;

    protected $table = 'user_hours_management';

    protected $fillable = [
        'user_id',
        'month',
        'total_hours',
        'consumed_hours',
    ];

    // Relationship with the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessor to calculate the remaining hours.
     */
    public function getRemainingHoursAttribute()
    {
        return $this->total_hours - $this->consumed_hours;
    }

    /**
     * Accessor to calculate overtime hours if any.
     */
    public function getOvertimeHoursAttribute()
    {
        $overtime = $this->consumed_hours - $this->total_hours;
        return $overtime > 0 ? $overtime : 0;
    }

    public function getTotalHoursAttribute($value)
    {
        return $value ?: '160:00';
    }

    public function getConsumedHoursAttribute($value)
    {
        return $value ?: '00:00';
    }
}
