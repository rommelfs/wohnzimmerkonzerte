<?php
require __DIR__ . '/../wp-content/plugins/wohnzimmer-core/selection.php';
$now = 1000;
$cases = [
    'empty' => [[], null],
    'earliest future, independent of order' => [[['id'=>3,'start'=>1400],['id'=>1,'start'=>900],['id'=>2,'start'=>1100]], 2],
    'last past event' => [[['id'=>1,'start'=>400],['id'=>2,'start'=>900]], 2],
    'cancelled future excluded' => [[['id'=>1,'start'=>1100,'cancelled'=>true],['id'=>2,'start'=>1200]], 2],
    'cancelled past excluded' => [[['id'=>1,'start'=>900,'cancelled'=>true],['id'=>2,'start'=>800]], 2],
    'missing date excluded' => [[['id'=>1,'start'=>0]], null],
    'at start still highlighted' => [[['id'=>1,'start'=>1000]], 1],
];
foreach ($cases as $name => [$events, $expected]) {
    $result = rl_select_highlight($events, $now);
    if (($result['id'] ?? null) !== $expected) throw new RuntimeException('Failed: ' . $name);
    echo "PASS: $name\n";
}
