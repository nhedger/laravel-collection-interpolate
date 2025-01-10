<?php

declare(strict_types=1);

namespace Hedger\LaravelCollectionInterpolate\Tests\Unit;

use Hedger\LaravelCollectionInterpolate\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class InterpolateTest extends TestCase
{
    #[Test]
    public function it_interpolates_linearly_by_default(): void
    {
        // Arrange
        $collection = collect([1, 2, null, 4, 5]);

        // Act
        $result = $collection->interpolate();

        // Assert
        $this->assertEquals([1, 2, 3, 4, 5], $result->all());
    }
}
