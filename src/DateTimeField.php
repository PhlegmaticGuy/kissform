<?php

namespace mindplay\kissform;

use DateTimeZone;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * This class provides information about a date/time input.
 *
 * You should specify the {@link $format} and call {@link setTimeZone()} - by default,
 * the timezone is obtained from {@date_default_timezone_get()} which is system-dependent.
 *
 * Format string is similar that of {@link date()} - see documentation below:
 *
 * {@link http://php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters}
 */
class DateTimeField extends Field implements RenderableField
{
    /**
     * @var string input date/time format string
     */
    public $format = 'Y-m-d H:i:s';

    /**
     * @var DateTimeZone input time-zone
     */
    public $timezone;

    /**
     * @var string[] map of HTML attributes to apply
     */
    public $attrs = array(
        'readonly' => 'readonly',
        'data-ui'  => 'datetimepicker',
    );

    /**
     * @param string $name field name
     * @param DateTimeZone|string|null $timezone input time-zone (or NULL to use the current default timezone)
     */
    public function __construct($name, $timezone = null)
    {
        parent::__construct($name);

        $this->setTimeZone($timezone);
    }

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

    /**
     * Attempts to parse the given input; returns NULL on failure.
     *
     * @param string $input
     *
     * @return int|null
     */
    public function parseInput($input)
    {
        $time = @date_create_from_format($this->format, $input, $this->timezone);

        return $time && ($time->format($this->format) == $input)
            ? $time->getTimestamp()
            : null;
    }

    /**
     * @param InputModel $model
     *
     * @return int|null timestamp
     *
     * @throws UnexpectedValueException if unable to parse the input
     */
    public function getValue(InputModel $model)
    {
        $input = $model->getInput($this);

        if (empty($input)) {
            return null;
        } else {
            $value = $this->parseInput($input);

            if ($value === null) {
                throw new UnexpectedValueException("invalid input");
            }

            return $value;
        }
    }

    /**
     * @param InputModel $model
     * @param int|null   $value timestamp
     *
     * @return void
     *
     * @throws InvalidArgumentException if the given value is unacceptable.
     */
    public function setValue(InputModel $model, $value)
    {
        if ($value === null) {
            $model->setInput($this, null);
        } elseif (is_int($value)) {
            $date = date_create(null, $this->timezone);
            $date->setTimestamp($value);

            $model->setInput($this, $date->format($this->format));
        } else {
            throw new InvalidArgumentException("integer timestamp expected");
        }
    }

    // TODO extract abstract base-class and add DateField

    /**
     * {@inheritdoc}
     */
    public function renderInput(InputRenderer $renderer, InputModel $model, array $attr)
    {
        return $renderer->buildInput($this, 'text', $attr + $this->attrs);
    }
}
