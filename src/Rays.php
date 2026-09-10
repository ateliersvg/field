<?php

declare(strict_types=1);

namespace Atelier\Field;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Internal\Clip;
use Atelier\Field\Internal\Guard;
use Atelier\Field\Internal\Num;
use Atelier\Svg\Element\Shape\PolygonElement;

/**
 * Wedges spreading from a point, the only field in the catalogue built on a
 * centre rather than on a horizon or a lattice.
 *
 * The centre is the anisotropy: every wedge widens with distance from it, so no
 * two places on the surface are alike and no tiling could produce it. It is
 * usually better placed outside the frame than inside, where the fan reads as a
 * light rather than as a wheel.
 *
 * Each wedge is drawn past the far corner and then cut to the frame by
 * geometry, so the group carries no clip path and no identifier.
 */
final class Rays extends AbstractField
{
    private function __construct(
        private readonly float $width,
        private readonly float $height,
        private readonly int $rays,
        private readonly float $focusX,
        private readonly float $focusY,
        private readonly float $duty,
    ) {
    }

    /**
     * @param float $width  area to cover
     * @param float $height area to cover
     * @param int   $rays   wedges around the full turn, 2 to 120
     * @param float $focusX where the centre stands across the frame, -1 to 2
     * @param float $focusY where the centre stands down the frame, -1 to 2
     * @param float $duty   share of its own step a wedge covers, 0 to 1
     *
     * @throws InvalidArgumentException if the area is not positive or a count is out of range
     */
    public static function create(float $width = 600.0, float $height = 400.0, int $rays = 16, float $focusX = 0.5, float $focusY = 1.08, float $duty = 0.5): self
    {
        Guard::positive($width, 'width');
        Guard::positive($height, 'height');
        Guard::atLeast($rays, 2, 'rays');
        Guard::atMost((float) $rays, 120.0, 'rays', '120');
        Guard::between($focusX, -1.0, 2.0, 'focusX');
        Guard::between($focusY, -1.0, 2.0, 'focusY');
        Guard::positive($duty, 'duty');
        Guard::fraction($duty, 'duty');

        return new self($width, $height, $rays, $focusX, $focusY, $duty);
    }

    public function width(): float
    {
        return $this->width;
    }

    public function height(): float
    {
        return $this->height;
    }

    public function shapes(): array
    {
        $cx = $this->width * $this->focusX;
        $cy = $this->height * $this->focusY;

        // Past the far corner whatever the centre, so a wedge always leaves the
        // frame instead of stopping inside it.
        $reach = hypot(
            max($cx, $this->width - $cx),
            max($cy, $this->height - $cy),
        ) * 1.5;

        $step = M_PI / $this->rays;
        $shapes = [];

        for ($ray = 0; $ray < $this->rays; ++$ray) {
            $from = -M_PI + 2 * $ray * $step;
            $to = $from + 2 * $step * $this->duty;

            $wedge = [
                [$cx, $cy],
                [$cx + $reach * cos($from), $cy + $reach * sin($from)],
                // The middle of the arc, so a wide wedge still reaches the
                // corner between its two edges.
                [$cx + $reach * cos(($from + $to) / 2), $cy + $reach * sin(($from + $to) / 2)],
                [$cx + $reach * cos($to), $cy + $reach * sin($to)],
            ];

            $cut = Clip::polygon($wedge, $this->width, $this->height);

            if (\count($cut) < 3) {
                continue;
            }

            $shapes[] = $this->painted($this->polygon($cut));
        }

        return $shapes;
    }

    /**
     * @param list<array{float, float}> $corners
     */
    private function polygon(array $corners): PolygonElement
    {
        $points = [];

        foreach ($corners as [$x, $y]) {
            $points[] = Num::format($x).','.Num::format($y);
        }

        $wedge = new PolygonElement();
        $wedge->setPoints(implode(' ', $points));

        return $wedge;
    }
}
