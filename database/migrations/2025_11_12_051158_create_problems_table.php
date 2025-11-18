<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('problems', function (Blueprint $table) {
            $table->id();
            $table->string('department');
            $table->string('service')->nullable(); 
            $table->string('priority');
            $table->text('statement');
            $table->string('client')->nullable(); 
            $table->string('created_by');
            $table->string('assigned_to')->nullable();
            $table->string('status')->default('pending');
            $table->json('transfer_history')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('problems');
    }
};