<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->json('permissions')->nullable();
            $table->boolean('is_system')->default(false); // super-admin: full access, locked
            $table->timestamps();
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });

        $now = now();
        foreach (config('permissions.defaults') as $slug => $role) {
            DB::table('roles')->insert([
                'slug' => $slug, 'name' => $role['name'], 'description' => $role['description'],
                'permissions' => json_encode(Role::expand($role['permissions'])),
                'is_system' => $slug === 'super-admin', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        // Existing administrators keep full access; everyone else becomes a learner.
        $ids = DB::table('roles')->pluck('id', 'slug');
        DB::table('users')->orderBy('id')->each(function ($user) use ($ids) {
            DB::table('role_user')->insert(['role_id' => $ids[$user->is_admin ? 'super-admin' : 'learner'], 'user_id' => $user->id]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};
