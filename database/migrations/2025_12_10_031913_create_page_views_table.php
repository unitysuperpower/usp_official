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
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->string('url', 500);
            $table->string('page_type', 50)->nullable(); // home, service, blog, etc
            $table->foreignId('viewable_id')->nullable();
            $table->string('viewable_type')->nullable(); // Service, Blog, etc
            $table->string('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('device_type', 20)->nullable(); // desktop, mobile, tablet
            $table->string('browser', 50)->nullable();
            $table->timestamp('viewed_at');
            $table->timestamps();
            
            $table->index(['page_type', 'viewed_at']);
            $table->index(['viewable_type', 'viewable_id']);
            $table->index('viewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
