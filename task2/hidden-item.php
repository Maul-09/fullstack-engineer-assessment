<?php

declare(strict_types=1);

const GRID = [
    '########',
    '#......#',
    '#.###..#',
    '#...#..#',
    '#X#....#',
    '########',
];

$option = $argv[1] ?? null;

if (count($argv) > 2 || ! in_array($option, [null, '--mark', '--self-test'], true)) {
    fwrite(STDERR, 'Usage: php task2/hidden-item.php [--mark|--self-test]'.PHP_EOL);
    exit(2);
}

$locations = findPossibleLocations(GRID);

if ($option === '--self-test') {
    exit(runSelfTest($locations));
}

fwrite(STDOUT, formatCoordinates($locations).PHP_EOL);

if ($option === '--mark') {
    fwrite(STDOUT, PHP_EOL.implode(PHP_EOL, markLocations(GRID, $locations)).PHP_EOL);
}

function findPossibleLocations(array $grid): array
{
    $start = findStart($grid);

    if ($start === null) {
        return [];
    }

    [$startX, $startY] = $start;
    $locations = [];

    for ($northY = $startY - 1; isOpen($grid, $startX, $northY); $northY--) {
        for ($eastX = $startX + 1; isOpen($grid, $eastX, $northY); $eastX++) {
            for ($southY = $northY + 1; isOpen($grid, $eastX, $southY); $southY++) {
                $locations["{$eastX},{$southY}"] = ['x' => $eastX, 'y' => $southY];
            }
        }
    }

    $locations = array_values($locations);
    usort(
        $locations,
        static fn (array $left, array $right): int => [$left['y'], $left['x']] <=> [$right['y'], $right['x']],
    );

    return $locations;
}

function findStart(array $grid): ?array
{
    foreach ($grid as $y => $row) {
        $x = strpos($row, 'X');

        if ($x !== false) {
            return [$x, $y];
        }
    }

    return null;
}

function isOpen(array $grid, int $x, int $y): bool
{
    return isset($grid[$y][$x]) && $grid[$y][$x] === '.';
}

function formatCoordinates(array $locations): string
{
    return implode(
        PHP_EOL,
        array_map(
            static fn (array $location): string => "({$location['x']},{$location['y']})",
            $locations,
        ),
    );
}

function markLocations(array $grid, array $locations): array
{
    foreach ($locations as $location) {
        $grid[$location['y']][$location['x']] = '$';
    }

    return $grid;
}

function runSelfTest(array $actualLocations): int
{
    $expectedLocations = [
        ['x' => 5, 'y' => 2],
        ['x' => 6, 'y' => 2],
        ['x' => 5, 'y' => 3],
        ['x' => 6, 'y' => 3],
        ['x' => 3, 'y' => 4],
        ['x' => 5, 'y' => 4],
        ['x' => 6, 'y' => 4],
    ];
    $expectedMarkedGrid = [
        '########',
        '#......#',
        '#.###$$#',
        '#...#$$#',
        '#X#$.$$#',
        '########',
    ];

    if ($actualLocations !== $expectedLocations) {
        fwrite(STDERR, 'Self-test koordinat gagal.'.PHP_EOL);

        return 1;
    }

    if (markLocations(GRID, $actualLocations) !== $expectedMarkedGrid) {
        fwrite(STDERR, 'Self-test marked grid gagal.'.PHP_EOL);

        return 1;
    }

    fwrite(STDOUT, 'PASS coordinates=7 marked_grid=PASS'.PHP_EOL);

    return 0;
}
