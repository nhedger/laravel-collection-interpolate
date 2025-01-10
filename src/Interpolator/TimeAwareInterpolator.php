<?php

declare(strict_types=1);

namespace Hedger\LaravelCollectionInterpolate\Interpolator;

use Illuminate\Support\Collection;

/**
 * Interpolates the values of the collection with respect to time.
 */
class TimeAwareInterpolator
{
    public static function interpolate(
        Collection $collection,
        string|null $valuePath,
        string|null $timePath,
    ): Collection
    {
        return collect();
    }
}
