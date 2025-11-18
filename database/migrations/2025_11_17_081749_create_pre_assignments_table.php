<?php
// database/migrations/2024_01_01_000001_create_pre_assignments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pre_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('department');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // One pre-assignment per department
            $table->unique(['department', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('pre_assignments');
    }
};