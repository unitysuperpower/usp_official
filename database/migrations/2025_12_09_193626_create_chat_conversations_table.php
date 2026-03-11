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
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session_id')->nullable(); // For guest users
            $table->string('subject')->nullable();
            $table->enum('status', ['active', 'closed', 'archived'])->default('active');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // Admin assigned
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('assigned_to');
            $table->index('last_message_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_conversations');
    }
};
