<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;

class ReactPage
{
    public static function render(string $page, array $props = [])
    {
        return view('react', [
            'page' => $page,
            'props' => self::serialize($props),
        ]);
    }

    private static function serialize(mixed $value): mixed
    {
        // Public content relations must never expose authors' private account data.
        if ($value instanceof User) {
            return $value->only(['id', 'name']);
        }
        if ($value instanceof Model) {
            return array_merge($value->attributesToArray(), self::serialize($value->getRelations()));
        }
        if ($value instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            return array_merge($value->toArray(), ['data' => self::serialize($value->items())]);
        }
        if ($value instanceof \Illuminate\Support\Collection) {
            return $value->map(fn ($item) => self::serialize($item))->all();
        }
        if (is_array($value)) {
            return array_map(fn ($item) => self::serialize($item), $value);
        }
        return $value instanceof Arrayable ? $value->toArray() : $value;
    }
}
