<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'web_link',
        'email',
        'onboard_date',
        'billing_type',
        'currency',
        'status',
        'description'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
