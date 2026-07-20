<?php

namespace App\Support;

use App\Models\Odp;
use Illuminate\Support\Collection;

class OdpAssignment
{
    public const ORDER_NAME_LENGTH = 15;

    public static function storedName(?string $name): string
    {
        return mb_substr(trim((string) $name), 0, self::ORDER_NAME_LENGTH);
    }

    public static function normalizedName(?string $name): string
    {
        return mb_strtolower(trim((string) $name));
    }

    public static function key(?string $name): string
    {
        return self::normalizedName(self::storedName($name));
    }

    public static function uniqueByStoredName(Collection $odps): Collection
    {
        return $odps
            ->groupBy(fn (Odp $odp) => self::key($odp->nama))
            ->filter(fn (Collection $matches) => $matches->count() === 1)
            ->map(fn (Collection $matches) => $matches->first());
    }

    public static function resolve(Collection $odps, ?string $name): ?Odp
    {
        $exact = $odps->filter(fn (Odp $odp) => self::normalizedName($odp->nama) === self::normalizedName($name));
        if ($exact->count() === 1) {
            return $exact->first();
        }

        return self::uniqueByStoredName($odps)->get(self::key($name));
    }

    public static function isStoredNameUnique(Collection $odps, Odp $odp): bool
    {
        return $odps->filter(fn (Odp $candidate) => self::key($candidate->nama) === self::key($odp->nama))->count() === 1;
    }
}
