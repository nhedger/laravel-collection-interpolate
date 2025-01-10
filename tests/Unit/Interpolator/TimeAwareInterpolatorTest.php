<?php

namespace Hedger\LaravelCollectionInterpolate\Tests\Unit\Interpolator;

use Hedger\LaravelCollectionInterpolate\InterpolationException;
use Hedger\LaravelCollectionInterpolate\Interpolator\TimeAwareInterpolator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TimeAwareInterpolatorTest extends TestCase
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
        TimeAwareInterpolator::interpolate($collection);
    }

    #[Test]
    public function it_ensures_timestamps_can_be_parsed_into_carbon_instances(): void
    {
        // Arrange
        $collection = collect([
            '*' =>['value' => 1],
        ]);

        // Expect
        $this->expectException(InterpolationException::class);
        $this->expectExceptionMessage('Timestamps must be instances of DateTimeInterface.');

        // Act
        TimeAwareInterpolator::interpolate($collection);
    }

    #[Test]
    public function it_interpolates_the_values_of_a_collection_with_respect_to_time(): void
    {
        // Arrange
        $collection = collect([
            '2021-01-01' => 1,
            '2021-01-02' => 2,
            '2021-01-03' => null,
            '2021-01-04' => 4,
        ]);

        // Act
        $result = TimeAwareInterpolator::interpolate($collection);

        // Assert
        $this->assertEquals([
            '2021-01-01' => 1,
            '2021-01-02' => 2,
            '2021-01-03' => 3.0,
            '2021-01-04' => 4,
        ], $result->all());
    }

    #[Test]
    public function it_interpolates_the_values_of_a_collection_with_respect_to_time_and_a_custom_value_path(): void
    {
        // Arrange
        $collection = collect([
            '2021-01-01' => ['data' => ['reading' => 1]],
            '2021-01-02' => ['data' => ['reading' => 2]],
            '2021-01-03' => ['data' => ['reading' => null]],
            '2021-01-04' => ['data' => ['reading' => 4]],
        ]);

        // Act
        $result = TimeAwareInterpolator::interpolate($collection, 'data.reading');

        // Assert
        $this->assertEquals([
            '2021-01-01' => ['data' => ['reading' => 1]],
            '2021-01-02' => ['data' => ['reading' => 2]],
            '2021-01-03' => ['data' => ['reading' => 3.0]],
            '2021-01-04' => ['data' => ['reading' => 4]],
        ], $result->all());
    }

    #[Test]
    public function it_interpolates_thes_values_of_a_collection_with_respect_to_time_with_a_custom_time_path(): void
    {
        // Arrange
        $collection = collect([
            ['timestamp' => '2021-01-01', 'value' => 1],
            ['timestamp' => '2021-01-02', 'value' => 2],
            ['timestamp' => '2021-01-03', 'value' => null],
            ['timestamp' => '2021-01-04', 'value' => 4],
        ]);

        // Act
        $result = TimeAwareInterpolator::interpolate(
            $collection,
            valuePath: 'value',
            timePath: 'timestamp',
        );

        // Assert
        $this->assertEquals([
            ['timestamp' => '2021-01-01', 'value' => 1],
            ['timestamp' => '2021-01-02', 'value' => 2],
            ['timestamp' => '2021-01-03', 'value' => 3.0],
            ['timestamp' => '2021-01-04', 'value' => 4],
        ], $result->all());
    }
}
