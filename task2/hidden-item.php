<?php

declare(strict_types=1);

const TASK_GRID = [
    '########',
    '#......#',
    '#.###..#',
    '#...#..#',
    '#X#....#',
    '########',
];

try {
    exit(run($argv));
} catch (InvalidArgumentException $exception) {
    fwrite(STDERR, 'ERROR: '.$exception->getMessage().PHP_EOL);
    fwrite(STDERR, 'Usage: php task2/hidden-item.php [--mark|--self-test]'.PHP_EOL);

    exit(2);
} catch (Throwable $exception) {
    fwrite(STDERR, 'ERROR: '.$exception->getMessage().PHP_EOL);

    exit(1);
}

function run(array $arguments): int
{
    $options = array_slice($arguments, 1);

    if (count($options) > 1 || ($options !== [] && ! in_array($options[0], ['--mark', '--self-test'], true))) {
        throw new InvalidArgumentException('Opsi tidak dikenali.');
    }

    $endpoints = findHiddenItemEndpoints(TASK_GRID);

    if ($options === ['--self-test']) {
        return runSelfTest($endpoints);
    }

    fwrite(STDOUT, formatCoordinates($endpoints).PHP_EOL);

    if ($options === ['--mark']) {
        fwrite(STDOUT, PHP_EOL.implode(PHP_EOL, markEndpoints(TASK_GRID, $endpoints)).PHP_EOL);
    }

    return 0;
}

function findHiddenItemEndpoints(array $grid): array
{
    assertValidGrid($grid);
    [$originX, $originY] = findOrigin($grid);
    $endpoints = [];

    for ($northY = $originY - 1; isClear($grid, $originX, $northY); $northY--) {
        for ($eastX = $originX + 1; isClear($grid, $eastX, $northY); $eastX++) {
            for ($southY = $northY + 1; isClear($grid, $eastX, $southY); $southY++) {
                $endpoints["{$eastX},{$southY}"] = ['x' => $eastX, 'y' => $southY];
            }
        }
    }

    $endpoints = array_values($endpoints);
    usort(
        $endpoints,
        static fn (array $left, array $right): int => [$left['y'], $left['x']] <=> [$right['y'], $right['x']],
    );

    return $endpoints;
}

function assertValidGrid(array $grid): void
{
    if ($grid === [] || $grid[0] === '') {
        throw new InvalidArgumentException('Grid tidak boleh kosong.');
    }

    $width = strlen($grid[0]);

    foreach ($grid as $row) {
        if (! is_string($row) || strlen($row) !== $width) {
            throw new InvalidArgumentException('Setiap baris grid harus memiliki panjang yang sama.');
        }
    }
}

function findOrigin(array $grid): array
{
    $origin = null;

    foreach ($grid as $y => $row) {
        for ($x = 0, $width = strlen($row); $x < $width; $x++) {
            if ($row[$x] !== 'X') {
                continue;
            }

            if ($origin !== null) {
                throw new InvalidArgumentException('Grid hanya boleh memiliki satu titik X.');
            }

            $origin = [$x, $y];
        }
    }

    if ($origin === null) {
        throw new InvalidArgumentException('Titik X tidak ditemukan pada grid.');
    }

    return $origin;
}

function isClear(array $grid, int $x, int $y): bool
{
    return isset($grid[$y][$x]) && $grid[$y][$x] === '.';
}

function formatCoordinates(array $endpoints): string
{
    return implode(
        PHP_EOL,
        array_map(
            static fn (array $endpoint): string => "({$endpoint['x']},{$endpoint['y']})",
            $endpoints,
        ),
    );
}

function markEndpoints(array $grid, array $endpoints): array
{
    foreach ($endpoints as $endpoint) {
        $grid[$endpoint['y']][$endpoint['x']] = '$';
    }

    return $grid;
}

function runSelfTest(array $actualEndpoints): int
{
    $expectedEndpoints = [
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

    if ($actualEndpoints !== $expectedEndpoints) {
        throw new RuntimeException('Self-test koordinat gagal. Aktual: '.formatCoordinates($actualEndpoints));
    }

    if (markEndpoints(TASK_GRID, $actualEndpoints) !== $expectedMarkedGrid) {
        throw new RuntimeException('Self-test marked grid gagal.');
    }

    fwrite(STDOUT, 'PASS coordinates=7 marked_grid=PASS'.PHP_EOL);

    return 0;
}
