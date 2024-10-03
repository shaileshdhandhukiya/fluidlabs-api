<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'company', 'phone', 'currency', 'email', 'website', 
        'office_address', 'city', 'state', 'country', 'zip_code', 
        'description', 'subscription_package', 'status','customer_name','billing_type'
    ];
}
