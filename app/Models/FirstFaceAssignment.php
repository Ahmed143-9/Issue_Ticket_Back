<?php
// app/Models/FirstFaceAssignment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FirstFaceAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'department',
        'type',
        'assigned_by',
        'assigned_at',
        'is_active'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    // Relationship with assigned user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship with user who made the assignment
    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}