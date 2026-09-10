<?php

declare(strict_types=1);

namespace Atelier\Field;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Internal\Guard;
use Atelier\Field\Internal\Num;
use Atelier\Svg\Element\Shape\CircleElement;

/**
 * A lattice of dots whose radius falls away from one point, the way a printed
 * halftone renders a lit sphere.
 *
 * The lattice itself is even. What varies is the ink each cell carries: a dot
 * near the light fills its cell, a dot at the far corner shrinks to a speck.
 * That gradient is the whole subject, and it is also why this cannot be a tile:
 * a tiling makes every cell equivalent, and here no two cells are.
 *
 * Distance is normalised by the farthest corner from the light, so the ramp
 * spans the frame whatever the light's position, and the darkest dot always
 * lands where the light is.
 */
final class Halftone extends AbstractField
{
    /** Radius of the largest dot, as a share of the spacing. */
    private const float FILL = 0.46;

    /** Radius of the smallest dot, as a share of the spacing. */
    private const float SPECK = 0.06;

    private function __construct(
        private readonly float $width,
        private readonly float $height,
        private readonly float $spacing,
        private readonly float $focusX,
        private readonly float $focusY,
    ) {
    }

    /**
     * @param float $width   area to cover
     * @param float $height  area to cover
     * @param float $spacing distance between two cells, at most a quarter of the smaller side
     * @param float $focusX  where the light stands across the frame, [0, 1)
     * @param float $focusY  where the light stands down the frame, [0, 1)
     *
     * @throws InvalidArgumentException if the area is not positive or the light stands outside the frame
     */
    public static function create(float $width = 600.0, float $height = 400.0, float $spacing = 13.0, float $focusX = 0.36, float $focusY = 0.36): self
    {
        Guard::positive($width, 'width');
        Guard::positive($height, 'height');
        Guard::positive($spacing, 'spacing');
        Guard::atMost($spacing, min($width, $height) / 4, 'spacing', 'a quarter of the smaller side');
        Guard::fraction($focusX, 'focusX');
        Guard::fraction($focusY, 'focusY');

        return new self($width, $height, $spacing, $focusX, $focusY);
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
        $lightX = $this->width * $this->focusX;
        $lightY = $this->height * $this->focusY;

        // The farthest corner sets the scale, so the palest dot lands on it and
        // the ramp always spans the whole frame.
        $reach = hypot(max($lightX, $this->width - $lightX), max($lightY, $this->height - $lightY));

        $columns = max(1, (int) round($this->width / $this->spacing));
        $rows = max(1, (int) round($this->height / $this->spacing));
        $stepX = $this->width / $columns;
        $stepY = $this->height / $rows;

        $shapes = [];

        // Cells are centred, so the lattice sits inside the frame with half a
        // cell of margin rather than a dot cut in two on every edge.
        for ($row = 0; $row < $rows; ++$row) {
            for ($column = 0; $column < $columns; ++$column) {
                $x = ($column + 0.5) * $stepX;
                $y = ($row + 0.5) * $stepY;
                $light = 1.0 - hypot($x - $lightX, $y - $lightY) / $reach;

                $dot = new CircleElement();
                $dot->setCx(Num::format($x));
                $dot->setCy(Num::format($y));
                $dot->setR(Num::format($this->spacing * (self::SPECK + (self::FILL - self::SPECK) * $light)));

                $shapes[] = $this->painted($dot);
            }
        }

        return $shapes;
    }
}
