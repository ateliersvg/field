<?php

declare(strict_types=1);

namespace Atelier\Field;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Internal\Guard;
use Atelier\Field\Internal\Num;
use Atelier\Svg\Element\PathElement;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * One closed curve with no straight edge and no obvious centre, filling the
 * frame it is given.
 *
 * The radius is drawn at a handful of angles and the points are joined by a
 * Catmull-Rom spline written out as cubic beziers, so the curve passes through
 * every one of them and its tangents match on both sides. Joining them with
 * arcs instead leaves a corner wherever two arcs meet, which is what makes a
 * hand-rolled blob read as a flower.
 *
 * The first point is reused as the last, so the seam closes with the same
 * tangent as any other node and cannot be found by looking.
 *
 * The radius is read against the half width and half height separately, which
 * is what lets a blob fill a frame that is not square instead of sitting in the
 * middle of it as a disc.
 */
final class Blob extends AbstractField
{
    private function __construct(
        private readonly float $width,
        private readonly float $height,
        private readonly int $nodes,
        private readonly float $irregularity,
        private readonly int $seed,
    ) {
    }

    /**
     * @param float $width        area to cover
     * @param float $height       area to cover
     * @param int   $nodes        points the curve passes through, 3 to 24
     * @param float $irregularity how far a radius may leave the mean, [0, 1)
     * @param int   $seed         same seed, same shape
     *
     * @throws InvalidArgumentException if the area is not positive or a count is out of range
     */
    public static function create(float $width = 400.0, float $height = 400.0, int $nodes = 7, float $irregularity = 0.28, int $seed = 1): self
    {
        Guard::positive($width, 'width');
        Guard::positive($height, 'height');
        Guard::atLeast($nodes, 3, 'nodes');
        Guard::atMost((float) $nodes, 24.0, 'nodes', '24');
        Guard::fraction($irregularity, 'irregularity');

        return new self($width, $height, $nodes, $irregularity, $seed);
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
        $blob = new PathElement();
        $blob->setD($this->outline());

        return [$this->painted($blob)];
    }

    /**
     * The closed curve, as one path.
     */
    private function outline(): string
    {
        $points = $this->nodes();
        $count = \count($points);
        $path = 'M'.Num::format($points[0][0]).' '.Num::format($points[0][1]);

        for ($node = 0; $node < $count; ++$node) {
            $before = $points[($node - 1 + $count) % $count];
            $from = $points[$node];
            $to = $points[($node + 1) % $count];
            $after = $points[($node + 2) % $count];

            // Catmull-Rom through four points, as the cubic bezier with the
            // same tangents. The sixth is the standard tension.
            $path .= 'C'
                .Num::format($from[0] + ($to[0] - $before[0]) / 6).' '
                .Num::format($from[1] + ($to[1] - $before[1]) / 6).' '
                .Num::format($to[0] - ($after[0] - $from[0]) / 6).' '
                .Num::format($to[1] - ($after[1] - $from[1]) / 6).' '
                .Num::format($to[0]).' '
                .Num::format($to[1]);
        }

        return $path.'Z';
    }

    /**
     * The points the curve passes through, one per angle.
     *
     * Angles are spaced evenly rather than drawn, so a blob never grows a
     * pinch where two nodes happen to land together.
     *
     * @return list<array{float, float}>
     */
    private function nodes(): array
    {
        $random = new Randomizer(new Xoshiro256StarStar($this->seed));

        $centreX = $this->width / 2;
        $centreY = $this->height / 2;

        $points = [];

        for ($node = 0; $node < $this->nodes; ++$node) {
            $angle = 2 * M_PI * $node / $this->nodes;
            $radius = $random->getFloat(1.0 - $this->irregularity, 1.0);

            $points[] = [
                $centreX + cos($angle) * $radius * $centreX,
                $centreY + sin($angle) * $radius * $centreY,
            ];
        }

        return $points;
    }
}
