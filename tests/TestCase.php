<?php

declare(strict_types=1);

namespace Hedger\LaravelCollectionInterpolate\Tests;

use Hedger\LaravelCollectionInterpolate\ServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function getPackageProviders($app): array
    {
        return [
            ServiceProvider::class,
        ];
    }
}
