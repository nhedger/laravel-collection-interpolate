<?php

declare(strict_types=1);

namespace Hedger\LaravelCollectionInterpolate\Interpolator;

use Hedger\LaravelCollectionInterpolate\InterpolationException;
use Illuminate\Support\Collection;

/**
 * Interpolates the values of the collection linearly.
 */
class LinearInterpolator
{
    public static function interpolate(
        Collection $collection,
        string|null $valuePath = null,
    ): Collection
    {
        // Make sure that all non-null items have a numeric value
        self::ensureNumbers($collection, $valuePath);

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
            function($item, $key) use (&$nullIndicesWithNeighbours, &$collection, $valuePath, $originalIndices) {
                $key = $originalIndices->search($key);
                $nullIndexWithNeighbours = $nullIndicesWithNeighbours->firstWhere('index', $key);

                // If the item is not null, return it as is
                if (is_null($nullIndexWithNeighbours)) {
                    return $item;
                }

                // Retrieve the preceding non-null neighbour
                $previousIndex = $originalIndices->search($nullIndexWithNeighbours['previousIndex']);
                $previousValue = match(is_null($valuePath)) {
                    true => $collection->get($previousIndex),
                    false => data_get($collection->get($previousIndex), $valuePath),
                };

                // Retrieve the following non-null neighbour
                $nextIndex = $originalIndices->search($nullIndexWithNeighbours['nextIndex']);
                $nextValue = match(is_null($valuePath)) {
                    true => $collection->get($nextIndex),
                    false => data_get($collection->get($nextIndex), $valuePath),
                };

                // Compute the distance between the item its neighbouring items
                $previousDistance = $key - $previousIndex;
                $nextDistance = $nextIndex - $key;

                // Interpolate the value
                $interpolatedValue = (
                    $previousValue * $nextDistance + $nextValue * $previousDistance
                ) / ($previousDistance + $nextDistance);

                return match(is_null($valuePath)) {
                    true => $interpolatedValue,
                    false => data_set($item, $valuePath, $interpolatedValue),
                };
            }
        );
    }

    private static function ensureNumbers(Collection $collection, string|null $valuePath): void
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
}
