<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->roles->contains('is_system', true);
    }

    /** Permission keys granted by all of the user's roles. */
    public function permissions(): array
    {
        if ($this->isSuperAdmin()) {
            return Role::catalog();
        }

        return $this->roles->flatMap(fn (Role $role) => $role->permissions ?? [])->unique()->values()->all();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles->contains(fn (Role $role) => $role->grants($permission));
    }

    /**
     * Replace the user's roles. `is_admin` stays as the "staff member" flag that admin-area
     * checks rely on: it is true whenever any role grants at least one permission.
     */
    public function syncRoles(array $roleIds): void
    {
        $this->roles()->sync($roleIds);
        $this->unsetRelation('roles');
        $this->forceFill(['is_admin' => $this->permissions() !== []])->save();
    }

    public function assignRole(string $slug): void
    {
        $this->syncRoles(array_unique([...$this->roles()->pluck('roles.id')->all(), Role::where('slug', $slug)->value('id')]));
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }
}
