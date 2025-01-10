<?php

declare(strict_types=1);

namespace Hedger\LaravelCollectionInterpolate;

use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function boot(): void
    {
        // Extend the Collection with the interpolate method
        Collection::mixin(new Interpolate());
    }
}
