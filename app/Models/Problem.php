<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Problem extends Model
{
    use HasFactory;

    protected $fillable = [
        'department',
        'priority',
        'status', 
        'statement',
        'created_by',
        'assigned_to',
        'transfer_history',
        'resolved_at'
    ];

    protected $casts = [
        'transfer_history' => 'array',
        'resolved_at' => 'datetime'
    ];

    protected $attributes = [
        'status' => 'pending'
    ];
}