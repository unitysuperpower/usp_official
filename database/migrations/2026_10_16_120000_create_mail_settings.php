<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->string('host')->nullable();
            $table->unsignedSmallInteger('port')->default(587);
            $table->string('encryption', 10)->default('tls'); // tls (STARTTLS), ssl (SMTPS) or none
            $table->string('username')->nullable();
            $table->text('password')->nullable(); // encrypted with the application key
            $table->string('from_address')->nullable();
            $table->string('from_name')->nullable();
            $table->string('admin_address')->nullable();
            $table->json('notifications')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_settings');
    }
};
