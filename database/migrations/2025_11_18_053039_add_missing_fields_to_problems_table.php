<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('problems', function (Blueprint $table) {
            // Add missing fields for auto-assignment
            $table->string('assignment_type')->nullable()->after('assigned_to');
            $table->string('submitted_for_approval_by')->nullable()->after('status');
            $table->timestamp('submitted_for_approval_at')->nullable()->after('submitted_for_approval_by');
            $table->string('approved_by')->nullable()->after('submitted_for_approval_at');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');
            $table->string('rejected_by')->nullable()->after('rejection_reason');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
        });
    }

    public function down(): void
    {
        Schema::table('problems', function (Blueprint $table) {
            $table->dropColumn([
                'assignment_type',
                'submitted_for_approval_by',
                'submitted_for_approval_at',
                'approved_by',
                'approved_at',
                'rejection_reason',
                'rejected_by',
                'rejected_at'
            ]);
        });
    }
};