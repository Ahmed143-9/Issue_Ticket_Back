<?php
// app/Models/FirstFaceAssignment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FirstFaceAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', // ✅ user_id add করুন
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

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); // ✅ user_id ব্যবহার করুন
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}