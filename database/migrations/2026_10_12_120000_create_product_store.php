<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('digital_products', function (Blueprint $table) {
            $table->id(); $table->string('slug')->unique(); $table->string('name');
            $table->string('summary', 500); $table->text('description')->nullable();
            $table->json('free_features')->nullable(); $table->json('pro_features')->nullable();
            $table->string('image_path')->nullable(); $table->boolean('published')->default(false);
            $table->timestamps();
        });
        Schema::create('product_plans', function (Blueprint $table) {
            $table->id(); $table->foreignId('digital_product_id')->constrained()->restrictOnDelete();
            $table->string('name'); $table->unsignedInteger('price_minor');
            $table->unsignedSmallInteger('term_months'); $table->unsignedInteger('site_limit')->nullable();
            $table->boolean('published')->default(false); $table->timestamps();
        });
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id(); $table->decimal('usd_pkr_rate', 12, 4)->nullable(); $table->timestamps();
        });
        Schema::create('product_orders', function (Blueprint $table) {
            $table->id(); $table->uuid('reference')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('digital_product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_plan_id')->constrained()->restrictOnDelete();
            $table->string('product_name'); $table->string('plan_name');
            $table->unsignedInteger('usd_minor'); $table->unsignedBigInteger('amount_minor');
            $table->decimal('usd_pkr_rate',12,4); $table->string('currency',3)->default('PKR');
            $table->unsignedSmallInteger('term_months'); $table->unsignedInteger('site_limit')->nullable();
            $table->unsignedBigInteger('renewal_license_id')->nullable()->index();
            $table->string('status',30)->default('pending')->index();
            $table->timestamp('quote_expires_at'); $table->timestamp('paid_at')->nullable(); $table->timestamps();
        });
        Schema::create('product_payments', function (Blueprint $table) {
            $table->id(); $table->foreignId('product_order_id')->constrained()->restrictOnDelete();
            $table->string('method'); $table->string('reference')->unique();
            $table->string('transaction_reference')->nullable(); $table->string('proof_path')->nullable();
            $table->string('status',30)->default('pending')->index();
            $table->string('gateway_environment')->nullable(); $table->string('gateway_code')->nullable();
            $table->string('gateway_reference')->nullable(); $table->timestamp('expires_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable(); $table->timestamp('reviewed_at')->nullable(); $table->timestamps();
            $table->unique(['method','transaction_reference']);
        });
        Schema::create('product_licenses', function (Blueprint $table) {
            $table->id(); $table->foreignId('product_order_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('digital_product_id')->constrained()->restrictOnDelete();
            $table->string('key_hash',64)->unique(); $table->text('key_secret'); $table->string('key_hint',20);
            $table->unsignedInteger('site_limit')->nullable();
            $table->string('status',30)->default('active'); $table->timestamp('expires_at');
            $table->text('admin_note')->nullable(); $table->timestamps();
        });
        Schema::create('license_activations', function (Blueprint $table) {
            $table->id(); $table->foreignId('product_license_id')->constrained()->cascadeOnDelete();
            $table->string('site_url',512); $table->string('site_hash',64);
            $table->text('token_secret'); $table->timestamp('last_checked_at')->nullable(); $table->timestamps();
            $table->unique(['product_license_id','site_hash']);
        });
        Schema::create('product_releases', function (Blueprint $table) {
            $table->id(); $table->foreignId('digital_product_id')->constrained()->restrictOnDelete();
            $table->string('version',30); $table->string('edition',10); $table->string('package_path');
            $table->string('sha256',64); $table->text('changelog')->nullable();
            $table->boolean('published')->default(false); $table->timestamps();
            $table->unique(['digital_product_id','version','edition']);
        });
    }
    public function down(): void
    {
        foreach (['product_releases','license_activations','product_licenses','product_payments','product_orders','store_settings','product_plans','digital_products'] as $table) { Schema::dropIfExists($table); }
    }
};
