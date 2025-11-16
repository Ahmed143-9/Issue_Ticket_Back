<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('problems', function (Blueprint $table) {
            $table->string('department');
            $table->enum('priority', ['High', 'Medium', 'Low']);
            $table->enum('status', ['pending', 'in_progress', 'done', 'pending_approval'])->default('pending');
            $table->text('statement');
            $table->string('created_by');
            $table->string('assigned_to')->nullable();
            $table->json('transfer_history')->nullable();
            $table->timestamp('resolved_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('problems', function (Blueprint $table) {
            $table->dropColumn([
                'department',
                'priority', 
                'status',
                'statement',
                'created_by',
                'assigned_to',
                'transfer_history',
                'resolved_at'
            ]);
        });
    }
};