<?php

declare(strict_types=1);

namespace Atelier\Field\Internal;

/**
 * A lattice of sample points over a frame.
 *
 * The fields reading a height or a direction all need the same thing: a set of
 * positions spread evenly across the area, with the count derived from a cell
 * size rather than asked for. Deriving it here keeps a cell square whatever the
 * frame measures, which is what stops a stretched cell from tilting every
 * triangle or every isoline the same way.
 *
 * @internal
 */
final class Grid
{
    public readonly int $columns;

    public readonly int $rows;

    public readonly float $stepX;

    public readonly float $stepY;

    public function __construct(
        public readonly float $width,
        public readonly float $height,
        float $cell,
    ) {
        $this->columns = max(1, (int) round($width / $cell));
        $this->rows = max(1, (int) round($height / $cell));
        $this->stepX = $width / $this->columns;
        $this->stepY = $height / $this->rows;
    }

    public function x(float $column): float
    {
        return $column * $this->stepX;
    }

    public function y(float $row): float
    {
        return $row * $this->stepY;
    }
}
