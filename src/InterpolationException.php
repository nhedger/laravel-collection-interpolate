<?php

namespace Hedger\LaravelCollectionInterpolate;

use Exception;

class InterpolationException extends Exception
{
    public static function notNumbers(): self
    {
        return new self('All non-null items must be numbers (int or float).');
    }

    public static function invalidTimestampFormat(): self
    {
        return new self('Timestamps must be instances of DateTimeInterface.');
    }
}
