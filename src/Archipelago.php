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
 * Islands: the filled level sets of a height field, drawn the way a map shades
 * land above successive altitudes.
 *
 * Contours draws the same crossings as open lines. Filling them needs closed
 * rings instead, and a ring only closes if the field never crosses the border
 * of the frame. A window that falls to nothing along every edge guarantees it,
 * and it is also what puts water around the archipelago rather than cutting an
 * island in half.
 *
 * Each level goes into one path with every ring it owns, filled even odd, so a
 * lagoon inside an island is a hole rather than a second island on top.
 */
final class Archipelago extends AbstractField
{
    /** How far in from the edge the field is brought down to nothing. */
    private const float SHORE = 0.16;

    /** Where the lowest coast is cut, on a field normalised to [0, 1]. */
    private const float SEA = 0.5;

    /** How much higher each level is cut than the one below it. */
    private const float STEP = 0.08;

    private function __construct(
        private readonly float $width,
        private readonly float $height,
        private readonly float $cell,
        private readonly int $levels,
        private readonly int $seed,
    ) {
    }

    /**
     * @param float $width  area to cover
     * @param float $height area to cover
     * @param float $cell   distance between two samples of the height field, at most a quarter of the smaller side
     * @param int   $levels coasts drawn, 1 to 6
     * @param int   $seed   same seed, same islands
     *
     * @throws InvalidArgumentException if the area is not positive or a count is out of range
     */
    public static function create(float $width = 600.0, float $height = 400.0, float $cell = 8.0, int $levels = 3, int $seed = 1): self
    {
        Guard::positive($width, 'width');
        Guard::positive($height, 'height');
        Guard::positive($cell, 'cell');
        Guard::atMost($cell, min($width, $height) / 4, 'cell', 'a quarter of the smaller side');
        Guard::atLeast($levels, 1, 'levels');
        Guard::atMost((float) $levels, 6.0, 'levels', '6');

        return new self($width, $height, $cell, $levels, $seed);
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
            $at = self::SEA + self::STEP * $level;
            $path = '';

            foreach (Isoline::loops(Isoline::segments($grid, $field, $at)) as $ring) {
                foreach ($ring as $index => [$x, $y]) {
                    $path .= (0 === $index ? 'M' : 'L').Num::format($x).' '.Num::format($y);
                }

                $path .= 'Z';
            }

            if ('' === $path) {
                continue;
            }

            $island = new PathElement();
            $island->setD($path);
            $island->setAttribute('fill-rule', 'evenodd');

            $shapes[] = $this->toned($island, $level, 0.30 + 0.56 * ($level / max(1, $this->levels - 1)));
        }

        return $shapes;
    }

    /**
     * The height at every lattice corner, brought down to nothing at the border.
     *
     * @return list<list<float>>
     */
    private function sample(Grid $grid): array
    {
        $noise = new Noise($this->seed);
        $margin = self::SHORE * min($this->width, $this->height);
        $field = [];

        for ($row = 0; $row <= $grid->rows; ++$row) {
            $line = [];

            for ($column = 0; $column <= $grid->columns; ++$column) {
                $x = $grid->x($column);
                $y = $grid->y($row);

                $edge = min($x, $y, $this->width - $x, $this->height - $y);
                $window = max(0.0, min(1.0, $edge / $margin));

                $line[] = ($noise->at($x * 0.016, $y * 0.016, 3) + 1) / 2
                    * $window * $window * (3 - 2 * $window);
            }

            $field[] = $line;
        }

        return $field;
    }
}
