<?php

declare(strict_types=1);

namespace Hedger\LaravelCollectionInterpolate;

use Closure;
use Hedger\LaravelCollectionInterpolate\Interpolator\LinearInterpolator;
use Hedger\LaravelCollectionInterpolate\Interpolator\TimeAwareInterpolator;
use Illuminate\Support\Collection;

/**
 * @mixin Collection
 */
class Interpolate
{
    public function interpolate(): Closure
    {
        return function (
            string $valuePath = null,
            string $timePath = null,
            string $mode = 'linear',
        ) {
            return match ($mode) {
                'linear' => LinearInterpolator::interpolate($this, $valuePath),
                'time' => TimeAwareInterpolator::interpolate($this, $valuePath, $timePath),
                default => throw new \InvalidArgumentException('Unsupported interpolation mode:' . $mode),
            };
        };
    }
}
