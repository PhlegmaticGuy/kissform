<?php

namespace mindplay\kissform;

use DateTimeZone;
use InvalidArgumentException;

/**
 * Abstract base class for timezone-aware date/time field types.
 */
abstract class TimeZoneAwareField extends Field
{
    /**
     * @var DateTimeZone input time-zone
     */
    public $timezone;

    /**
     * @param DateTimeZone|string|null $timezone input time-zone (or NULL to use the current default timezone)
     */
    public function setTimeZone($timezone)
    {
        if ($timezone === null) {
            $timezone = new DateTimeZone(date_default_timezone_get());
        } else if (is_string($timezone)) {
            $timezone = new DateTimeZone($timezone);
        } else if (! $timezone instanceof DateTimeZone) {
            throw new InvalidArgumentException('DateTimeZone or string expected, ' . gettype($timezone) . ' given');
        }

        $this->timezone = $timezone;
    }
}
