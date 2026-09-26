<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ReactPage
{
    public static function render(string $page, array $props = [])
    {
        if (in_array($page, ['services.index', 'services.category', 'blogs.index', 'blogs.category'], true)) {
            $listing = $props['services'] ?? $props['blogs'] ?? null;
            if ($listing instanceof LengthAwarePaginator) {
                abort_if($listing->currentPage() > $listing->lastPage(), 404);
            }
        }
        $props = self::serialize($props);

        return view('react', [
            'page' => $page,
            'props' => $props,
            'seo' => Seo::forPage($page, $props),
        ]);
    }

    private static function serialize(mixed $value): mixed
    {
        // Public content relations must never expose authors' private account data.
        if ($value instanceof User) {
            return $value->only(['id', 'name']);
        }
        if ($value instanceof Model) {
            $relations = [];
            foreach ($value->getRelations() as $key => $relation) {
                $relations[$value::$snakeAttributes ? Str::snake($key) : $key] = self::serialize($relation);
            }

            return array_merge($value->attributesToArray(), $relations);
        }
        if ($value instanceof LengthAwarePaginator) {
            return array_merge($value->toArray(), ['data' => self::serialize($value->items())]);
        }
        if ($value instanceof Collection) {
            return $value->map(fn ($item) => self::serialize($item))->all();
        }
        if (is_array($value)) {
            return array_map(fn ($item) => self::serialize($item), $value);
        }

        return $value instanceof Arrayable ? $value->toArray() : $value;
    }
}
