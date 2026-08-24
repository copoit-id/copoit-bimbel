<?php

namespace App\Support;

use Illuminate\Http\Request;

class Pagination
{
    /**
     * @return list<int>
     */
    public static function options(): array
    {
        return [5, 9, 10, 12, 15, 20, 25, 30, 50, 100];
    }

    public static function perPage(int $default): int
    {
        $perPage = app(Request::class)->integer('per_page');

        return in_array($perPage, self::options(), true)
            ? $perPage
            : $default;
    }
}
