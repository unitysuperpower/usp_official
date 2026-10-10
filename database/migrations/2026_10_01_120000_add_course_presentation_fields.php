<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('cover_image')->nullable();
            $table->string('cover_alt')->nullable();
            $table->string('visual_theme', 20)->default('mint');
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('carousel_position')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('courses', fn (Blueprint $table) => $table->dropColumn(['cover_image', 'cover_alt', 'visual_theme', 'is_featured', 'carousel_position']));
    }
};
