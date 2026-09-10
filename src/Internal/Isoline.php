<?php

declare(strict_types=1);

namespace Atelier\Field\Internal;

/**
 * Marching squares over a sampled height field.
 *
 * Every cell of the lattice is read as four corners above or below a level, and
 * the sixteen cases give the segments the level crosses it with. The crossing
 * is interpolated along the edge rather than taken at its middle, which is the
 * difference between a curve and a staircase.
 *
 * Contours draws the segments as they come. A filled level needs them stitched
 * into closed rings first, which loops() does.
 *
 * @internal
 */
final class Isoline
{
    /**
     * The segments one level crosses the lattice with.
     *
     * @param list<list<float>> $field height at every lattice corner
     *
     * @return list<array{array{float, float}, array{float, float}}>
     */
    public static function segments(Grid $grid, array $field, float $at): array
    {
        $segments = [];

        for ($row = 0; $row < $grid->rows; ++$row) {
            for ($column = 0; $column < $grid->columns; ++$column) {
                $topLeft = $field[$row][$column];
                $topRight = $field[$row][$column + 1];
                $bottomRight = $field[$row + 1][$column + 1];
                $bottomLeft = $field[$row + 1][$column];

                $case = ($topLeft > $at ? 8 : 0)
                    | ($topRight > $at ? 4 : 0)
                    | ($bottomRight > $at ? 2 : 0)
                    | ($bottomLeft > $at ? 1 : 0);

                if (0 === $case || 15 === $case) {
                    continue;
                }

                $left = [$grid->x($column), $grid->y($row) + self::cut($topLeft, $bottomLeft, $at) * $grid->stepY];
                $right = [$grid->x($column + 1), $grid->y($row) + self::cut($topRight, $bottomRight, $at) * $grid->stepY];
                $top = [$grid->x($column) + self::cut($topLeft, $topRight, $at) * $grid->stepX, $grid->y($row)];
                $bottom = [$grid->x($column) + self::cut($bottomLeft, $bottomRight, $at) * $grid->stepX, $grid->y($row + 1)];

                foreach (self::caseSegments($case, $top, $right, $bottom, $left) as $segment) {
                    $segments[] = $segment;
                }
            }
        }

        return $segments;
    }

    /**
     * The segments stitched into rings, end to end.
     *
     * A field that falls away from every border crosses no border, so every
     * ring closes. A ring that does not is dropped: an open run cannot be
     * filled, and filling is the only reason to stitch.
     *
     * @param list<array{array{float, float}, array{float, float}}> $segments
     *
     * @return list<list<array{float, float}>>
     */
    public static function loops(array $segments): array
    {
        $index = [];

        foreach ($segments as $position => [$from, $to]) {
            $index[self::key($from)][] = $position;
            $index[self::key($to)][] = $position;
        }

        $used = [];
        $loops = [];

        foreach ($segments as $position => [$start, $end]) {
            if (isset($used[$position])) {
                continue;
            }

            $used[$position] = true;
            $loop = [$start, $end];

            while (true) {
                $next = null;

                foreach ($index[self::key($end)] ?? [] as $candidate) {
                    if (!isset($used[$candidate])) {
                        $next = $candidate;

                        break;
                    }
                }

                if (null === $next) {
                    break;
                }

                $used[$next] = true;
                [$from, $to] = $segments[$next];
                $end = self::key($from) === self::key($end) ? $to : $from;
                $loop[] = $end;
            }

            if (\count($loop) > 3 && self::key($loop[0]) === self::key($end)) {
                $loops[] = $loop;
            }
        }

        return $loops;
    }

    /**
     * Where along an edge the level falls, in [0, 1].
     *
     * Two equal corners would divide by zero and never straddle a level in the
     * first place, so the midpoint they fall back to is never drawn.
     */
    private static function cut(float $from, float $to, float $at): float
    {
        $span = $to - $from;

        return 0.0 === $span ? 0.5 : ($at - $from) / $span;
    }

    /**
     * The segments of one marching squares case.
     *
     * The two saddles, 5 and 10, are cut the way the corners lean: joining the
     * other pair of edges there would close a ridge that the height field says
     * is open.
     *
     * @param array{float, float} $top
     * @param array{float, float} $right
     * @param array{float, float} $bottom
     * @param array{float, float} $left
     *
     * @return list<array{array{float, float}, array{float, float}}>
     */
    private static function caseSegments(int $case, array $top, array $right, array $bottom, array $left): array
    {
        return match ($case) {
            1, 14 => [[$left, $bottom]],
            2, 13 => [[$bottom, $right]],
            3, 12 => [[$left, $right]],
            4, 11 => [[$top, $right]],
            5 => [[$left, $top], [$bottom, $right]],
            6, 9 => [[$top, $bottom]],
            7, 8 => [[$left, $top]],
            10 => [[$left, $bottom], [$top, $right]],
            default => [],
        };
    }

    /**
     * @param array{float, float} $point
     */
    private static function key(array $point): string
    {
        return Num::format($point[0]).','.Num::format($point[1]);
    }
}
