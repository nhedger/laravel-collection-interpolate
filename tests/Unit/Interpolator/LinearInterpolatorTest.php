<?php

namespace Hedger\LaravelCollectionInterpolate\Tests\Unit\Interpolator;

use Hedger\LaravelCollectionInterpolate\InterpolationException;
use Hedger\LaravelCollectionInterpolate\Interpolator\LinearInterpolator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LinearInterpolatorTest extends TestCase
{
    #[Test]
    public function it_ensures_that_all_non_null_items_have_a_number_value(): void
    {
        // Arrange
        $collection = collect([1, 2, '3', 4, 5]);

        // Expect
        $this->expectException(InterpolationException::class);
        $this->expectExceptionMessage('All non-null items must be numbers (int or float).');

        // Act
        LinearInterpolator::interpolate($collection);
    }

    #[Test]
    public function it_interpolates_the_values_of_a_numerically_indexed_collection(): void
    {
        // Arrange
        $collection = collect([1, 2, null, 4, 5]);

        // Act
        $result = LinearInterpolator::interpolate($collection);

        // Assert
        $this->assertEquals([1, 2, 3, 4, 5], $result->all());
    }

    #[Test]
    public function it_interpolates_the_values_of_a_numerically_indexed_collection_with_a_custom_value_path(): void
    {
        // Arrange
        $collection = collect([
            ['data' => ['reading' => 1]],
            ['data' => ['reading' => 2]],
            ['data' => ['reading' => null]],
            ['data' => ['reading' => 4]],
            ['data' => ['reading' => 5]],
        ]);

        // Act
        $result = LinearInterpolator::interpolate($collection, valuePath: 'data.reading');

        // Assert
        $this->assertEquals([
            ['data' => ['reading' => 1]],
            ['data' => ['reading' => 2]],
            ['data' => ['reading' => 3]],
            ['data' => ['reading' => 4]],
            ['data' => ['reading' => 5]],
        ], $result->all());
    }

    #[Test]
    public function it_interpolates_the_values_of_a_non_numerically_indexed_collection()
    {
        // Arrange
        $collection = collect([
            'a' => 1,
            'b' => 2,
            'c' => null,
            'd' => 4,
            'e' => 5
        ]);

        // Act
        $result = LinearInterpolator::interpolate($collection);

        // Assert
        $this->assertEquals([
            'a' => 1,
            'b' => 2,
            'c' => 3,
            'd' => 4,
            'e' => 5,
        ], $result->all());
    }

    #[Test]
    public function it_returns_the_original_collection_if_there_are_no_values_to_interpolate(): void
    {
        // Arrange
        $collection = collect([1, 2, 3, 4, 5]);

        // Act
        $result = LinearInterpolator::interpolate($collection);

        // Assert
        $this->assertSame($collection, $result);
    }

    #[Test]
    public function it_does_not_interpolate_the_values_of_null_items_that_are_not_surrounded_by_non_null_items(): void
    {
        // Arrange
        $collection = collect([null, 3, null, 5]);

        // Act
        $result = LinearInterpolator::interpolate($collection);

        // Assert
        $this->assertEquals([null, 3, 4, 5], $result->all());
    }
}
