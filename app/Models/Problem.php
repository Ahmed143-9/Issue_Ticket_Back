<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Problem extends Model
{
    use HasFactory;

    protected $fillable = [
        'department',
        'service',
        'priority',
        'status', 
        'statement',
        'client',
        'images', // 🔥 ADD THIS
        'created_by',
        'assigned_to',
        'transfer_history',
        'resolved_at'
    ];

    protected $casts = [
        'images' => 'array', // 🔥 ADD THIS - automatically convert to/from JSON
        'transfer_history' => 'array',
        'resolved_at' => 'datetime'
    ];

    protected $attributes = [
        'status' => 'pending'
    ];
}