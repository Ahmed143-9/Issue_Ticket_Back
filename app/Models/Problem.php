<?php
// app/Models/Problem.php

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
        'statement',
        'client',
        'created_by',
        'assigned_to',
        'status',
        'images',
        'transfer_history',
        'resolved_at',
        'assignment_type',
        'submitted_for_approval_by',
        'submitted_for_approval_at',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'rejected_by',
        'rejected_at'
    ];

    protected $casts = [
        'images' => 'array',
        'transfer_history' => 'array',
        'resolved_at' => 'datetime',
        'submitted_for_approval_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime'
    ];

    // ✅ Relationships যোগ করুন
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function approvalSubmitter()
    {
        return $this->belongsTo(User::class, 'submitted_for_approval_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}