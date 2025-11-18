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
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // ✅ User relationship
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ✅ Assigner relationship
    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}