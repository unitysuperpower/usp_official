<?php

namespace App\Support;

use Illuminate\Http\Request;

class AdminListing
{
    public static function perPage(Request $request, int $default = 20): int
    {
        $data = $request->validate(['per_page' => 'nullable|integer|in:10,20,50,100']);

        return (int) ($data['per_page'] ?? $default);
    }
}
