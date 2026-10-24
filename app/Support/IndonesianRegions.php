<?php

namespace App\Support;

final class IndonesianRegions
{
    public static function all(): array
    {
        static $regions;

        return $regions ??= json_decode(file_get_contents(resource_path('data/indonesia-regions.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function provinces(): array
    {
        return array_keys(self::all());
    }

    public static function cities(mixed $province): array
    {
        return is_string($province) ? (self::all()[$province] ?? []) : [];
    }
}
