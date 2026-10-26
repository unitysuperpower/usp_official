<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = ['slug', 'name', 'description', 'permissions'];
    protected $casts = ['permissions' => 'array', 'is_system' => 'boolean'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /** Every permission key in config/permissions.php. */
    public static function catalog(): array
    {
        return collect(config('permissions.groups'))->flatMap(fn ($items) => array_keys($items))->all();
    }

    /** Resolve "*" (all) and "!key" (exclude) shorthands into explicit, known keys. */
    public static function expand(array $permissions): array
    {
        $all = self::catalog();
        $keys = in_array('*', $permissions, true) ? $all : array_intersect($permissions, $all);
        $excluded = collect($permissions)->filter(fn ($p) => str_starts_with($p, '!'))->map(fn ($p) => substr($p, 1))->all();

        return array_values(array_diff($keys, $excluded));
    }

    public function grants(string $permission): bool
    {
        return $this->is_system || in_array($permission, $this->permissions ?? [], true);
    }
}
