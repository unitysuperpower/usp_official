<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('digital_products', function (Blueprint $table) {
            $table->string('tagline', 160)->nullable();
            $table->string('category', 60)->default('WordPress plugin');
            $table->json('screenshots')->nullable(); // [{path, caption}]
            $table->json('faq')->nullable(); // [{q, a}]
            $table->json('requirements')->nullable(); // {wordpress, php, tested}
            $table->json('activation_steps')->nullable(); // one instruction per item
            $table->string('docs_url', 2000)->nullable();
            $table->string('support_url', 2000)->nullable();
            $table->unsignedInteger('sort_order')->default(100);
        });
        Schema::table('product_plans', function (Blueprint $table) {
            $table->string('description', 255)->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_featured')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('product_plans', fn (Blueprint $table) => $table->dropColumn(['description', 'features', 'is_featured']));
        Schema::table('digital_products', fn (Blueprint $table) => $table->dropColumn(['tagline', 'category', 'screenshots', 'faq', 'requirements', 'activation_steps', 'docs_url', 'support_url', 'sort_order']));
    }
};
