<?php
// database/migrations/2025_11_18_xxxxxx_update_first_face_assignments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // যদি table already exists, তাহলে structure update করুন
        if (Schema::hasTable('first_face_assignments')) {
            Schema::table('first_face_assignments', function (Blueprint $table) {
                // ✅ Check if columns exist, যদি না থাকে তবে add করুন
                if (!Schema::hasColumn('first_face_assignments', 'type')) {
                    $table->enum('type', ['all', 'specific'])->default('specific');
                }
                if (!Schema::hasColumn('first_face_assignments', 'assigned_by')) {
                    $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');
                }
                // ✅ Remove user_name column যদি exists করে
                if (Schema::hasColumn('first_face_assignments', 'user_name')) {
                    $table->dropColumn('user_name');
                }
            });
        }
    }

    public function down()
    {
        // Rollback logic
    }
};