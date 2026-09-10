<?php

declare(strict_types=1);

namespace Atelier\Field\Internal;

/**
 * Clips polygon geometry and path centrelines to the frame.
 *
 * Several fields use shapes that naturally run past the frame: rulings laid along the diagonal
 * so they still cover the frame once turned, and rings drawn out to the far
 * corner so a source near an edge still reaches the other side. Strokes can
 * extend half their width beyond the clipped centreline.
 *
 * Cutting them here rather than with a `<clipPath>` keeps the group free of
 * identifiers. A field is content appended to a document, and content carrying
 * an id has to worry about colliding with whatever is already in that document.
 *
 * @internal
 */
final class Clip
{
    /**
     * The part of a segment inside the frame, by the Liang-Barsky cut, or null
     * when the segment misses it.
     *
     * @return array{float, float, float, float}|null
     */
    public static function segment(float $x1, float $y1, float $x2, float $y2, float $width, float $height): ?array
    {
        $dx = $x2 - $x1;
        $dy = $y2 - $y1;

        $enter = 0.0;
        $leave = 1.0;

        foreach ([[-$dx, $x1], [$dx, $width - $x1], [-$dy, $y1], [$dy, $height - $y1]] as [$slope, $room]) {
            if (0.0 === $slope) {
                // Parallel to this edge: outside it, nothing of the segment is
                // ever inside the frame.
                if ($room < 0.0) {
                    return null;
                }

                continue;
            }

            $at = $room / $slope;

            if ($slope < 0.0) {
                $enter = max($enter, $at);
            } else {
                $leave = min($leave, $at);
            }
        }

        if ($enter > $leave) {
            return null;
        }

        return [$x1 + $enter * $dx, $y1 + $enter * $dy, $x1 + $leave * $dx, $y1 + $leave * $dy];
    }

    /**
     * A sampled polyline, clipped segment by segment to the frame.
     *
     * A closed curve crossing an edge comes back as several runs, which is what
     * a ring reaching past a corner has to become.
     *
     * @param list<array{float, float}> $points
     *
     * @return list<list<array{float, float}>>
     */
    public static function runs(array $points, float $width, float $height): array
    {
        $runs = [];
        $run = [];

        for ($index = 1, $count = \count($points); $index < $count; ++$index) {
            [$x1, $y1] = $points[$index - 1];
            [$x2, $y2] = $points[$index];
            $segment = self::segment($x1, $y1, $x2, $y2, $width, $height);

            if (null === $segment) {
                if ([] !== $run) {
                    $runs[] = $run;
                    $run = [];
                }

                continue;
            }

            // Clamp intersection round-off, without changing the original samples.
            $from = [max(0.0, min($width, $segment[0])), max(0.0, min($height, $segment[1]))];
            $to = [max(0.0, min($width, $segment[2])), max(0.0, min($height, $segment[3]))];

            if ($from === $to) {
                continue;
            }

            // An outside excursion must not acquire a connecting line along the border.
            if ([] !== $run && ($x1 < 0.0 || $x1 > $width || $y1 < 0.0 || $y1 > $height)) {
                $runs[] = $run;
                $run = [];
            }

            if ([] === $run) {
                $run[] = $from;
            }

            $run[] = $to;
        }

        if ([] !== $run) {
            $runs[] = $run;
        }

        return $runs;
    }

    /**
     * The part of a convex polygon lying in nx*x + ny*y <= limit, by the
     * Sutherland-Hodgman cut.
     *
     * @param list<array{float, float}> $polygon
     *
     * @return list<array{float, float}>
     */
    public static function halfPlane(array $polygon, float $nx, float $ny, float $limit): array
    {
        $clipped = [];
        $corners = \count($polygon);

        for ($corner = 0; $corner < $corners; ++$corner) {
            [$fromX, $fromY] = $polygon[$corner];
            [$toX, $toY] = $polygon[($corner + 1) % $corners];

            $here = $nx * $fromX + $ny * $fromY - $limit;
            $there = $nx * $toX + $ny * $toY - $limit;

            if ($here <= 0.0) {
                $clipped[] = [$fromX, $fromY];
            }

            if (($here < 0.0 && $there > 0.0) || ($here > 0.0 && $there < 0.0)) {
                $ratio = $here / ($here - $there);
                $clipped[] = [$fromX + $ratio * ($toX - $fromX), $fromY + $ratio * ($toY - $fromY)];
            }
        }

        return $clipped;
    }

    /**
     * The part of a convex polygon inside the frame, cut against its four
     * sides in turn. An empty list means the polygon missed the frame.
     *
     * @param list<array{float, float}> $polygon
     *
     * @return list<array{float, float}>
     */
    public static function polygon(array $polygon, float $width, float $height): array
    {
        foreach ([[-1.0, 0.0, 0.0], [1.0, 0.0, $width], [0.0, -1.0, 0.0], [0.0, 1.0, $height]] as [$nx, $ny, $limit]) {
            $polygon = self::halfPlane($polygon, $nx, $ny, $limit);

            if ([] === $polygon) {
                return [];
            }
        }

        return $polygon;
    }
}
