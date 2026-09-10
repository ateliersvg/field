<?php

declare(strict_types=1);

namespace Atelier\Field;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Internal\Clip;
use Atelier\Field\Internal\Guard;
use Atelier\Field\Internal\Noise;
use Atelier\Field\Internal\Num;
use Atelier\Svg\Element\PathElement;

/**
 * A lattice laid in perspective over a relief, receding to a horizon.
 *
 * Depth is a division: a row at depth v is drawn at a distance from the horizon
 * of the frame's reach over v, so even steps in depth crowd on the screen the
 * way a road does. Columns fan from the vanishing point for the same reason,
 * and the height a point is lifted by is scaled the same way, so a far ridge
 * flattens without being computed any differently.
 *
 * This is the only field with a depth axis, and the horizon is what makes it
 * anisotropic: the top of the frame is the far distance and the bottom is at
 * the reader's feet.
 */
final class Terrain extends AbstractField
{
    /** How far across the world the near row reaches, in units of its depth. */
    private const float SPAN = 3.4;

    /** Depth of the near row, where 1 puts it on the bottom edge. */
    private const float NEAR = 0.5;

    /** Depth of the far row. */
    private const float FAR = 4.5;

    private function __construct(
        private readonly float $width,
        private readonly float $height,
        private readonly int $rows,
        private readonly int $columns,
        private readonly float $horizon,
        private readonly float $relief,
        private readonly float $thickness,
        private readonly int $seed,
    ) {
    }

    /**
     * @param float $width     area to cover
     * @param float $height    area to cover
     * @param int   $rows      rows of the lattice, 2 to 80
     * @param int   $columns   columns of the lattice, 2 to 80
     * @param float $horizon   where the horizon stands down the frame, [0, 1)
     * @param float $relief    how far a point may be lifted, 0 to 1
     * @param float $thickness line width of the near rows
     * @param int   $seed      same seed, same relief
     *
     * @throws InvalidArgumentException if the area is not positive or a count is out of range
     */
    public static function create(float $width = 600.0, float $height = 400.0, int $rows = 15, int $columns = 19, float $horizon = 0.36, float $relief = 0.6, float $thickness = 0.9, int $seed = 1): self
    {
        Guard::positive($width, 'width');
        Guard::positive($height, 'height');
        Guard::atLeast($rows, 2, 'rows');
        Guard::atMost((float) $rows, 80.0, 'rows', '80');
        Guard::atLeast($columns, 2, 'columns');
        Guard::atMost((float) $columns, 80.0, 'columns', '80');
        Guard::fraction($horizon, 'horizon');
        Guard::between($relief, 0.0, 1.0, 'relief');
        Guard::positive($thickness, 'thickness');

        return new self($width, $height, $rows, $columns, $horizon, $relief, $thickness, $seed);
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
        $noise = new Noise($this->seed);
        $sky = $this->height * $this->horizon;
        $reach = $this->height - $sky;

        $lattice = [];

        for ($row = 0; $row < $this->rows; ++$row) {
            $depth = self::NEAR + (self::FAR - self::NEAR) * $row / ($this->rows - 1);
            $scale = $reach / $depth;
            $points = [];

            for ($column = 0; $column < $this->columns; ++$column) {
                $across = -self::SPAN + 2 * self::SPAN * $column / ($this->columns - 1);

                $points[] = [
                    $this->width / 2 + $across * $scale * 1.55,
                    $sky + $scale - $noise->at($across * 1.1, $depth * 0.9, 2) * $this->relief * $scale * 0.42,
                ];
            }

            $lattice[] = $points;
        }

        $shapes = [];

        // Rows first, so the crests read before the lines running into them.
        for ($row = 0; $row < $this->rows; ++$row) {
            $near = 1.0 - $row / ($this->rows - 1);

            foreach (Clip::runs($lattice[$row], $this->width, $this->height) as $run) {
                $shapes[] = $this->trace($run, $this->thickness * (0.55 + 0.45 * $near), 0.35 + 0.65 * $near);
            }
        }

        for ($column = 0; $column < $this->columns; ++$column) {
            $line = [];

            for ($row = 0; $row < $this->rows; ++$row) {
                $line[] = $lattice[$row][$column];
            }

            foreach (Clip::runs($line, $this->width, $this->height) as $run) {
                $shapes[] = $this->trace($run, $this->thickness * 0.8, 0.55);
            }
        }

        return $shapes;
    }

    /**
     * @param list<array{float, float}> $points
     */
    private function trace(array $points, float $thickness, float $strength): PathElement
    {
        $path = '';

        foreach ($points as $index => [$x, $y]) {
            $path .= (0 === $index ? 'M' : 'L').Num::format($x).' '.Num::format($y);
        }

        $line = new PathElement();
        $line->setD($path);

        $traced = $this->outlined($line, $thickness);
        $traced->setAttribute('stroke-opacity', Num::format($strength));

        return $traced;
    }
}
