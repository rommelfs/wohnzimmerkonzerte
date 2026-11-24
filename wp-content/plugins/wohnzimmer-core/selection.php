<?php
/** Pure selection logic, shared by the theme and regression tests. */
function rl_select_highlight(array $events, int $now): ?array {
    $events = array_values(array_filter($events, static fn($e) => empty($e['cancelled']) && ($e['start'] ?? 0) > 0));
    $upcoming = array_values(array_filter($events, static fn($e) => $e['start'] >= $now));
    usort($upcoming, static fn($a, $b) => $a['start'] <=> $b['start']);
    if ($upcoming) return $upcoming[0];
    usort($events, static fn($a, $b) => $b['start'] <=> $a['start']);
    return $events[0] ?? null;
}
