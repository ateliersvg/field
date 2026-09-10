<?php

declare(strict_types=1);

namespace Atelier\Field;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Internal\Grid;
use Atelier\Field\Internal\Guard;
use Atelier\Field\Internal\Isoline;
use Atelier\Field\Internal\Noise;
use Atelier\Field\Internal\Num;
use Atelier\Svg\Element\PathElement;

/**
 * Isolines read off a height field, the way a map draws relief.
 *
 * The height is sampled on a lattice and each cell is cut by marching squares:
 * the four corners are compared to a level, the case says which edges the line
 * crosses, and the crossing is placed by interpolating between the two corner
 * heights.
 *
 * That interpolation is the whole difference between a contour and a staircase.
 * Taking the middle of a crossed edge instead costs nothing to write and gives
 * every isoline the same set of 45 degree corners, which reads as a lattice
 * artefact rather than as terrain.
 *
 * One path carries one level, so a level can be toned as a whole and a reader
 * follows a single line around the frame.
 */
final class Contours extends AbstractField
{
    private function __construct(
        private readonly float $width,
        private readonly float $height,
        private readonly float $cell,
        private readonly int $levels,
        private readonly float $thickness,
        private readonly int $seed,
    ) {
    }

    /**
     * @param float $width     area to cover
     * @param float $height    area to cover
     * @param float $cell      distance between two samples of the height field
     * @param int   $levels    isolines drawn between the low and the high ground, 1 to 40
     * @param float $thickness line width
     * @param int   $seed      same seed, same relief
     *
     * @throws InvalidArgumentException if the area is not positive or a count is out of range
     */
    public static function create(float $width = 600.0, float $height = 400.0, float $cell = 8.0, int $levels = 12, float $thickness = 1.0, int $seed = 1): self
    {
        Guard::positive($width, 'width');
        Guard::positive($height, 'height');
        Guard::positive($cell, 'cell');
        Guard::atMost($cell, min($width, $height) / 4, 'cell', 'a quarter of the smaller side');
        Guard::atLeast($levels, 1, 'levels');
        Guard::atMost((float) $levels, 40.0, 'levels', '40');
        Guard::positive($thickness, 'thickness');

        return new self($width, $height, $cell, $levels, $thickness, $seed);
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
        $field = $this->sample($grid);
        $shapes = [];

        for ($level = 0; $level < $this->levels; ++$level) {
            // Levels sit inside the range rather than on its ends, where an
            // isoline would either vanish or trace the border of the frame.
            $at = ($level + 1) / ($this->levels + 1);
            $path = $this->isoline($grid, $field, $at);

            if ('' === $path) {
                continue;
            }

            $line = new PathElement();
            $line->setD($path);

            $shapes[] = $this->outlined($line, $this->thickness);
        }

        return $shapes;
    }

    /**
     * The height at every lattice corner, in [0, 1].
     *
     * @return list<list<float>>
     */
    private function sample(Grid $grid): array
    {
        $noise = new Noise($this->seed);
        $field = [];

        for ($row = 0; $row <= $grid->rows; ++$row) {
            $line = [];

            for ($column = 0; $column <= $grid->columns; ++$column) {
                $height = $noise->at(
                    $grid->x($column) / $this->width * 3.2,
                    $grid->y($row) / $this->height * 3.2 * ($this->height / $this->width),
                    3,
                );

                $line[] = ($height + 1.0) / 2.0;
            }

            $field[] = $line;
        }

        return $field;
    }

    /**
     * One level, as the segments crossing every cell of the lattice.
     *
     * @param list<list<float>> $field
     */
    private function isoline(Grid $grid, array $field, float $at): string
    {
        $path = '';

        foreach (Isoline::segments($grid, $field, $at) as [$from, $to]) {
            $path .= 'M'.Num::format($from[0]).' '.Num::format($from[1])
                .'L'.Num::format($to[0]).' '.Num::format($to[1]);
        }

        return $path;
    }
}
