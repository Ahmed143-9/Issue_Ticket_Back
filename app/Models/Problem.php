<?php
// app/Models/Problem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Problem extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'department',
        'priority',
        'status',
        'reported_by',
        'assigned_to',
        'first_face_assigned_to',
        'assigned_at',
        'resolved_at',
        'resolution_notes'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'resolved_at' => 'datetime'
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}