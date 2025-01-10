<?php

declare(strict_types=1);

namespace Hedger\LaravelCollectionInterpolate\Interpolator;

use Carbon\Carbon;
use Exception;
use Hedger\LaravelCollectionInterpolate\InterpolationException;
use Illuminate\Support\Collection;

/**
 * Interpolates the values of the collection with respect to time.
 */
class TimeAwareInterpolator
{
    public static function interpolate(
        Collection $collection,
        string|null $valuePath = null,
        string|null $timePath = null,
    ): Collection
    {
        // Make sure that all timestamps are instances of DateTimeInterface
        self::validateTimestamps($collection, $timePath);

        // Make sure that all non-null values are numbers
        self::validateValues($collection, $valuePath);

        // Build a map of the original indices to the numerical indices
        $originalIndices = $collection->keys();

        // Collect the collection values to be able to search by numerical index
        $indexedCollection = $collection->values();

        // Find the indices of all the items that have a null value.
        $nullIndices = $indexedCollection
            ->filter(fn($value) => match(is_null($valuePath)) {
                true => is_null($value),
                false => is_null(data_get($value, $valuePath)),
            })
            ->keys();

        // If there are no null values, return the collection as is.
        if ($nullIndices->isEmpty()) {
            return $collection;
        }

        // For each item that has a null value, find the indices of the
        // neighbouring items that have a non-null value.
        /** @var Collection $nullIndicesWithNeighbours */
        $nullIndicesWithNeighbours = $nullIndices->map(function(mixed $index) use (&$indexedCollection) {


            // Find the first item with a non-null value that precedes the null item
            for ($i = $index - 1; $i >= 0; $i--) {
                if (!is_null($indexedCollection->get($i))) {
                    $previousIndex = $i;
                    break;
                }
            }

            // Find the first item with a non-null value that follows the null item
            for ($i = $index + 1; $i < $indexedCollection->count(); $i++) {
                if (!is_null($indexedCollection->get($i))) {
                    $nextIndex = $i;
                    break;
                }
            }

            return [
                'index' => $index,
                'previousIndex' => $previousIndex ?? null,
                'nextIndex' => $nextIndex ?? null,
            ];
        });

        // Filter out the items that don't have enough neighbouring items to interpolate
        /** @var Collection $nullIndicesWithNeighbours */
        $nullIndicesWithNeighbours = $nullIndicesWithNeighbours
            ->filter(fn($item) => !is_null($item['previousIndex']) && !is_null($item['nextIndex']));

        // If there are no items to interpolate, return the collection as is
        if ($nullIndicesWithNeighbours->isEmpty()) {
            return $collection;
        }

        // Interpolate the values of the items that have enough neighbouring items
        return $collection->map(
            function($item, $key) use (&$nullIndicesWithNeighbours, &$collection, $valuePath, $timePath, $originalIndices) {
                $keyIndex = $originalIndices->search($key);
                $nullIndexWithNeighbours = $nullIndicesWithNeighbours->firstWhere('index', $keyIndex);

                // If the item is not null, return it as is
                if (is_null($nullIndexWithNeighbours)) {
                    return $item;
                }

                // Retrieve the preceding non-null neighbour
                $previousIndex = $nullIndexWithNeighbours['previousIndex'];
                $previousTimestamp = Carbon::parse(match (is_null($timePath)) {
                    true => $originalIndices->get($previousIndex),
                    false => data_get($collection->get($previousIndex), $timePath),
                });
                $previousOriginalIndex = $originalIndices->get($previousIndex);
                $previousValue = match(is_null($valuePath)) {
                    true => $collection->get($previousOriginalIndex),
                    false => data_get($collection->get($previousOriginalIndex), $valuePath),
                };

                // Retrieve the following non-null neighbour
                $nextIndex = $nullIndexWithNeighbours['nextIndex'];
                $nextTimestamp = Carbon::parse(match(is_null($timePath)) {
                    true => $originalIndices->get($nextIndex),
                    false => data_get($collection->get($nextIndex), $timePath),
                });
                $nextOriginalIndex = $originalIndices->get($nextIndex);
                $nextValue = match(is_null($valuePath)) {
                    true => $collection->get($nextOriginalIndex),
                    false => data_get($collection->get($nextOriginalIndex), $valuePath),
                };

                $timestamp = Carbon::parse(match(is_null($timePath)) {
                    true => $key,
                    false => data_get($item, $timePath),
                });

                // Compute the time difference between the current item and the neighbours
                $timeDiffWithPrevious = $timestamp->diffInMicroseconds($previousTimestamp);
                $timeDiffBetweenNeighbours = $nextTimestamp->diffInMicroseconds($previousTimestamp);

                $interpolatedValue =
                    $previousValue +
                    $timeDiffWithPrevious *
                    (($nextValue - $previousValue) / $timeDiffBetweenNeighbours);


                if(is_null($valuePath)) {
                    return $interpolatedValue;
                }

                return data_set($item, $valuePath, $interpolatedValue);
            }
        );
    }

    private static function validateValues(Collection $collection, string|null $valuePath): void
    {
        $collection->each(function($item) use ($valuePath) {
            $value = match(is_null($valuePath)) {
                true => $item,
                false => data_get($item, $valuePath),
            };

            if (!is_int($value) && !is_float($value) && !is_null($value)) {
                throw InterpolationException::notNumbers();
            }
        });
    }

    private static function validateTimestamps(Collection $collection, string|null $timePath): void
    {
        $collection->each(function($item, $key) use ($timePath) {
            $time = match(is_null($timePath)) {
                true => $key,
                false => data_get($item, $timePath),
            };

            try {
                Carbon::parse($time);
            } catch (Exception $e) {
                throw InterpolationException::invalidTimestampFormat();
            }
        });
    }
}
