<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_payments', function (Blueprint $table) {
            $table->string('proof_path')->nullable()->change();
            $table->json('review_history')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('gateway_environment', 10)->nullable();
            $table->string('gateway_code', 20)->nullable();
            $table->string('gateway_reference', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('course_payments', function (Blueprint $table) {
            $table->dropColumn(['review_history', 'expires_at', 'gateway_environment', 'gateway_code', 'gateway_reference']);
        });
    }
};
