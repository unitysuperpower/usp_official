<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        // The Admin role gets the new appearance permission (Super Admin has every permission already).
        $admin = DB::table('roles')->where('slug', 'admin')->first();
        if ($admin) {
            $permissions = json_decode($admin->permissions ?? '[]', true) ?: [];
            DB::table('roles')->where('id', $admin->id)->update(['permissions' => json_encode(array_values(array_unique([...$permissions, 'settings.theme'])))]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
