# PHP Business Days

Dependency-free business-day calculations: skip weekends and holidays when adding days, counting days, or listing a date range.

## Why

"Add 5 days" is almost never what a deadline means — it means 5 *working* days. Every project ends up re-implementing the same weekend-skipping loop, and most versions get holidays wrong or hard-code Saturday/Sunday. This class does it once, correctly: custom weekends (Friday+Saturday for Gulf countries), fixed dates and recurring month-day holidays, and a small, predictable API.

## Usage

```php
require 'src/BusinessDays.php';

use Azanumer\BusinessDays\BusinessDays;

$bd = new BusinessDays();

// Is it a working day?
$bd->isBusinessDay('2026-11-18'); // true (Wednesday)
$bd->isBusinessDay('2026-11-21'); // false (Saturday)

// Add 5 business days — skips the weekend.
$bd->addBusinessDays('2026-11-18', 5)->format('Y-m-d'); // 2026-11-25

// Count business days between two dates (exclusive of start, inclusive of end).
$bd->businessDaysBetween('2026-11-18', '2026-11-25'); // 5

// List every business day in a range.
$bd->getBusinessDays('2026-11-23', '2026-11-29');
// ['2026-11-23', '2026-11-24', '2026-11-25', '2026-11-26', '2026-11-27']

// Custom weekends + holidays (fixed 'Y-m-d' or recurring 'm-d').
$gulf = new BusinessDays([5, 6], ['2026-12-02', '12-25']);
$gulf->nextBusinessDay('2026-12-01')->format('Y-m-d'); // 2026-12-03
```

## API

| Method | Description |
|---|---|
| `__construct(array $weekendDays = [6, 7], array $holidays = [])` | Weekend days are ISO-8601 numbers (1 = Mon … 7 = Sun). Holidays are `'Y-m-d'` fixed dates or `'m-d'` recurring dates. |
| `isBusinessDay($date)` | True when the date is neither a weekend day nor a holiday. |
| `isHoliday($date)` | True for a fixed or recurring holiday, regardless of weekend. |
| `addBusinessDays($date, int $days)` | Move forward (or backward with a negative number) by N business days. `0` returns the date itself, or the next business day when it isn't one. |
| `nextBusinessDay($date)` / `previousBusinessDay($date)` | Strictly next / previous business day. |
| `businessDaysBetween($start, $end)` | Count, exclusive of start and inclusive of end; negative when `$end` is before `$start`. |
| `getBusinessDays($start, $end)` | Every business day in the range as `Y-m-d` strings. |

All methods accept a `DateTimeInterface`, a date string, or a Unix timestamp, and always return `DateTimeImmutable` in the input's timezone. Requires PHP 7.4+.

## Example

Run `php examples/business-days-example.php` for a runnable demo of every method.
