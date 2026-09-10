<?php

declare(strict_types=1);

namespace Atelier\Field;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Internal\Grid;
use Atelier\Field\Internal\Guard;
use Atelier\Field\Internal\Noise;
use Atelier\Field\Internal\Num;
use Atelier\Svg\Element\ElementInterface;
use Atelier\Svg\Element\Shape\PolygonElement;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * A frame cut into triangles, each one flat and shaded by the height under it.
 *
 * The vertices come from a lattice pushed off its nodes, and the diagonal of a
 * cell turns with the parity of that cell. Both are needed: a lattice with no
 * jitter reads as graph paper, and a lattice jittered but cut the same way
 * every time reads as graph paper with a hatch over it. What has to disappear
 * is the grid, not the regularity of the sizes.
 *
 * Edges on the border of the frame stay pinned, so the triangles cover the area
 * exactly instead of leaving a ragged margin.
 */
final class LowPoly extends AbstractField
{
    /** How far a node may leave its own cell, as a share of the step. */
    private const float JITTER = 0.42;

    private function __construct(
        private readonly float $width,
        private readonly float $height,
        private readonly float $cell,
        private readonly int $tones,
        private readonly int $seed,
    ) {
    }

    /**
     * @param float $width  area to cover
     * @param float $height area to cover
     * @param float $cell   side of one cell, so two triangles
     * @param int   $tones  distinct shades, at least 2
     * @param int   $seed   same seed, same cut and same relief
     *
     * @throws InvalidArgumentException if the area is not positive or a count is out of range
     */
    public static function create(float $width = 600.0, float $height = 400.0, float $cell = 60.0, int $tones = 6, int $seed = 1): self
    {
        Guard::positive($width, 'width');
        Guard::positive($height, 'height');
        Guard::positive($cell, 'cell');
        Guard::atMost($cell, min($width, $height) / 2, 'cell', 'half the smaller side');
        Guard::atLeast($tones, 2, 'tones');
        Guard::atMost((float) $tones, 24.0, 'tones', '24');

        return new self($width, $height, $cell, $tones, $seed);
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
        $grid = new Grid($this->width, $this->height, $this->cell);
        $nodes = $this->nodes($grid);
        $noise = new Noise($this->seed);
        $shapes = [];

        for ($row = 0; $row < $grid->rows; ++$row) {
            for ($column = 0; $column < $grid->columns; ++$column) {
                $topLeft = $nodes[$row][$column];
                $topRight = $nodes[$row][$column + 1];
                $bottomRight = $nodes[$row + 1][$column + 1];
                $bottomLeft = $nodes[$row + 1][$column];

                // The diagonal turns with the parity of the cell, which is what
                // keeps the cut from reading as one hatch across the frame.
                $triangles = 0 === ($row + $column) % 2
                    ? [[$topLeft, $topRight, $bottomRight], [$topLeft, $bottomRight, $bottomLeft]]
                    : [[$topLeft, $topRight, $bottomLeft], [$topRight, $bottomRight, $bottomLeft]];

                foreach ($triangles as $triangle) {
                    $shapes[] = $this->facet($triangle, $noise);
                }
            }
        }

        return $shapes;
    }

    /**
     * Every lattice node, pushed off itself except on the border of the frame.
     *
     * @return list<list<array{float, float}>>
     */
    private function nodes(Grid $grid): array
    {
        $random = new Randomizer(new Xoshiro256StarStar($this->seed));
        $nodes = [];

        for ($row = 0; $row <= $grid->rows; ++$row) {
            $line = [];

            for ($column = 0; $column <= $grid->columns; ++$column) {
                $x = $grid->x($column);
                $y = $grid->y($row);

                // Both draws happen whatever the node, so a node on the border
                // spends the same pair as any other and the field does not
                // change shape when the frame does.
                $offsetX = $random->getFloat(-self::JITTER, self::JITTER) * $grid->stepX;
                $offsetY = $random->getFloat(-self::JITTER, self::JITTER) * $grid->stepY;

                if ($column > 0 && $column < $grid->columns) {
                    $x += $offsetX;
                }

                if ($row > 0 && $row < $grid->rows) {
                    $y += $offsetY;
                }

                $line[] = [$x, $y];
            }

            $nodes[] = $line;
        }

        return $nodes;
    }

    /**
     * One triangle, toned by the height under its middle.
     *
     * @param list<array{float, float}> $triangle
     */
    private function facet(array $triangle, Noise $noise): ElementInterface
    {
        $centreX = ($triangle[0][0] + $triangle[1][0] + $triangle[2][0]) / 3;
        $centreY = ($triangle[0][1] + $triangle[1][1] + $triangle[2][1]) / 3;

        $height = ($noise->at($centreX / $this->width * 2.6, $centreY / $this->height * 2.6, 2) + 1.0) / 2.0;
        $tone = min($this->tones - 1, (int) ($height * $this->tones));

        $points = [];

        foreach ($triangle as [$x, $y]) {
            $points[] = Num::format($x).','.Num::format($y);
        }

        $facet = new PolygonElement();
        $facet->setPoints(implode(' ', $points));

        return $this->toned($facet, $tone, 0.14 + 0.62 * ($tone / max(1, $this->tones - 1)));
    }
}
