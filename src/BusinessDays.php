<?php

declare(strict_types=1);

namespace Azanumer\BusinessDays;

/**
 * Dependency-free business-day calculations.
 *
 * A "business day" is any day that is not a weekend day and not a holiday.
 * Weekends default to Saturday + Sunday but can be customised (e.g. Friday
 * + Saturday for Gulf countries). Holidays can be fixed dates (YYYY-MM-DD)
 * or recurring month-day strings (MM-DD, e.g. '12-25' for Christmas).
 *
 * All methods accept DateTimeInterface|string|int (Unix timestamp) inputs
 * and always return DateTimeImmutable results in the input's timezone.
 */
final class BusinessDays
{
    /** @var int[] ISO-8601 weekday numbers treated as weekend (1 = Mon … 7 = Sun). */
    private array $weekendDays;

    /** @var array<string, true> Fixed holidays, keyed 'Y-m-d'. */
    private array $fixedHolidays = [];

    /** @var array<string, true> Recurring holidays, keyed 'm-d'. */
    private array $recurringHolidays = [];

    /**
     * @param int[]    $weekendDays       ISO-8601 weekday numbers (1-7). Default [6, 7].
     * @param string[] $holidays          Fixed 'Y-m-d' dates and/or recurring 'm-d' dates.
     */
    public function __construct(array $weekendDays = [6, 7], array $holidays = [])
    {
        foreach ($weekendDays as $day) {
            $day = (int) $day;
            if ($day < 1 || $day > 7) {
                throw new \InvalidArgumentException('Weekend days must be ISO-8601 numbers 1 (Mon) to 7 (Sun).');
            }
        }
        $this->weekendDays = array_values(array_unique($weekendDays));

        foreach ($holidays as $holiday) {
            $this->addHoliday((string) $holiday);
        }
    }

    /**
     * Add one holiday: 'Y-m-d' for a fixed date, 'm-d' for a recurring one.
     */
    public function addHoliday(string $holiday): self
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $holiday)) {
            $this->fixedHolidays[$holiday] = true;
        } elseif (preg_match('/^\d{2}-\d{2}$/', $holiday)) {
            $this->recurringHolidays[$holiday] = true;
        } else {
            throw new \InvalidArgumentException("Holiday must be 'Y-m-d' or 'm-d', got '{$holiday}'.");
        }
        return $this;
    }

    /**
     * Is the given date a business day?
     *
     * @param \DateTimeInterface|string|int $date
     */
    public function isBusinessDay($date): bool
    {
        $date = $this->toDate($date);
        if (in_array((int) $date->format('N'), $this->weekendDays, true)) {
            return false;
        }
        if (isset($this->fixedHolidays[$date->format('Y-m-d')])) {
            return false;
        }
        if (isset($this->recurringHolidays[$date->format('m-d')])) {
            return false;
        }
        return true;
    }

    /**
     * Is the given date a holiday (fixed or recurring, regardless of weekend)?
     *
     * @param \DateTimeInterface|string|int $date
     */
    public function isHoliday($date): bool
    {
        $date = $this->toDate($date);
        return isset($this->fixedHolidays[$date->format('Y-m-d')])
            || isset($this->recurringHolidays[$date->format('m-d')]);
    }

    /**
     * Move forward (or backward) by N business days.
     * $days = 0 returns the date itself when it is a business day,
     * otherwise the next business day.
     *
     * @param \DateTimeInterface|string|int $date
     */
    public function addBusinessDays($date, int $days): \DateTimeImmutable
    {
        $date = $this->toDate($date);

        if ($days === 0) {
            return $this->isBusinessDay($date) ? $date : $this->nextBusinessDay($date);
        }

        $step      = $days > 0 ? 1 : -1;
        $remaining = abs($days);
        $current   = $date;

        while ($remaining > 0) {
            $current = $current->modify(($step > 0 ? '+1 day' : '-1 day'));
            if ($this->isBusinessDay($current)) {
                $remaining--;
            }
        }

        return $current;
    }

    /**
     * Next business day strictly after the given date.
     *
     * @param \DateTimeInterface|string|int $date
     */
    public function nextBusinessDay($date): \DateTimeImmutable
    {
        return $this->addBusinessDays($date, 1);
    }

    /**
     * Previous business day strictly before the given date.
     *
     * @param \DateTimeInterface|string|int $date
     */
    public function previousBusinessDay($date): \DateTimeImmutable
    {
        return $this->addBusinessDays($date, -1);
    }

    /**
     * Count business days between two dates, exclusive of $start, inclusive
     * of $end (so businessDaysBetween(Mon, Tue) = 1). Returns a negative
     * number when $end is before $start.
     *
     * @param \DateTimeInterface|string|int $start
     * @param \DateTimeInterface|string|int $end
     */
    public function businessDaysBetween($start, $end): int
    {
        $start = $this->toDate($start)->setTime(0, 0, 0);
        $end   = $this->toDate($end)->setTime(0, 0, 0);

        if ($start == $end) {
            return 0;
        }

        $forward = $start < $end;
        $from    = $forward ? $start : $end;
        $to      = $forward ? $end : $start;

        $count   = 0;
        $current = $from;
        while ($current < $to) {
            $current = $current->modify('+1 day');
            if ($this->isBusinessDay($current)) {
                $count++;
            }
        }

        return $forward ? $count : -$count;
    }

    /**
     * List every business day from $start to $end inclusive, as Y-m-d strings.
     * Returns an empty array when $end is before $start.
     *
     * @param \DateTimeInterface|string|int $start
     * @param \DateTimeInterface|string|int $end
     * @return string[]
     */
    public function getBusinessDays($start, $end): array
    {
        $start = $this->toDate($start)->setTime(0, 0, 0);
        $end   = $this->toDate($end)->setTime(0, 0, 0);

        $days    = [];
        $current = $start;
        while ($current <= $end) {
            if ($this->isBusinessDay($current)) {
                $days[] = $current->format('Y-m-d');
            }
            $current = $current->modify('+1 day');
        }

        return $days;
    }

    /**
     * Normalise a date input to a midnight DateTimeImmutable.
     *
     * @param \DateTimeInterface|string|int $date
     */
    private function toDate($date): \DateTimeImmutable
    {
        if ($date instanceof \DateTimeImmutable) {
            return $date->setTime(0, 0, 0);
        }
        if ($date instanceof \DateTime) {
            return \DateTimeImmutable::createFromMutable($date)->setTime(0, 0, 0);
        }
        if (is_int($date)) {
            return (new \DateTimeImmutable('@' . $date))->setTimezone(new \DateTimeZone(date_default_timezone_get()))->setTime(0, 0, 0);
        }
        if (is_string($date)) {
            try {
                return (new \DateTimeImmutable($date))->setTime(0, 0, 0);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException("Cannot parse date string '{$date}'.", 0, $e);
            }
        }
        throw new \InvalidArgumentException('Date must be a DateTimeInterface, date string, or Unix timestamp.');
    }
}
