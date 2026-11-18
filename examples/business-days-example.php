<?php

declare(strict_types=1);

require __DIR__ . '/../src/BusinessDays.php';

use Azanumer\BusinessDays\BusinessDays;

// Default weekends: Saturday + Sunday.
$bd = new BusinessDays();

// 2026-11-18 is a Wednesday.
var_dump($bd->isBusinessDay('2026-11-18')); // true
var_dump($bd->isBusinessDay('2026-11-21')); // false (Saturday)

// Add 5 business days: skips the weekend.
echo $bd->addBusinessDays('2026-11-18', 5)->format('Y-m-d'), PHP_EOL; // 2026-11-25

// Business days between two dates (exclusive of start, inclusive of end).
echo $bd->businessDaysBetween('2026-11-18', '2026-11-25'), PHP_EOL; // 5

// List the business days in a week.
print_r($bd->getBusinessDays('2026-11-23', '2026-11-29'));
// ['2026-11-23', '2026-11-24', '2026-11-25', '2026-11-26', '2026-11-27']

// Custom weekends (Friday + Saturday) and holidays — fixed and recurring.
$gulf = new BusinessDays([5, 6], ['2026-12-02', '12-25']);
var_dump($gulf->isBusinessDay('2026-12-02')); // false (UAE National Day)
var_dump($gulf->isBusinessDay('2026-12-25')); // false (recurring Christmas)
var_dump($gulf->isBusinessDay('2026-11-22')); // true  (Sunday is a working day with Fri+Sat weekends)
echo $gulf->nextBusinessDay('2026-12-01')->format('Y-m-d'), PHP_EOL; // 2026-12-03 (Wed 2nd is a holiday, Thu 3rd is next)
