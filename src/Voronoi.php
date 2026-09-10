<?php

declare(strict_types=1);

namespace Atelier\Field;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Internal\Clip;
use Atelier\Field\Internal\Guard;
use Atelier\Field\Internal\Num;
use Atelier\Svg\Element\Shape\PolygonElement;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * The cells of a Voronoi partition, cut against the frame.
 *
 * Every part of the surface belongs to its nearest site, and a cell is the set
 * of points closer to one site than to any other. It is obtained by cutting the
 * frame with the perpendicular bisector of each pair, so the cells are convex,
 * they tile the frame exactly, and three of them meet at each vertex.
 *
 * Sites are relaxed toward the middle of their own cell before the partition is
 * kept. A handful of relaxation passes turns a scatter with clumps and voids
 * into a mesh of comparable cells, which is what separates cracked glaze from
 * confetti. Relaxing forever would land on a honeycomb, and the point is to
 * stop short of it.
 *
 * The counterpart in atelier/pattern computes the same partition on a torus so
 * that it joins with itself. Here nothing has to join, which is what lets the
 * sites move.
 */
final class Voronoi extends AbstractField
{
    private function __construct(
        private readonly float $width,
        private readonly float $height,
        private readonly int $sites,
        private readonly int $relax,
        private readonly float $thickness,
        private readonly int $tones,
        private readonly int $seed,
    ) {
    }

    /**
     * @param float $width     area to cover
     * @param float $height    area to cover
     * @param int   $sites     seeds thrown into the frame, at least 2
     * @param int   $relax     passes moving each site onto the middle of its cell, 0 to 12
     * @param float $thickness line width between two cells, 0 to leave them unstroked
     * @param int   $tones     distinct shades, at least 2
     * @param int   $seed      same seed, same partition
     *
     * @throws InvalidArgumentException if the area is not positive or a count is out of range
     */
    public static function create(float $width = 600.0, float $height = 400.0, int $sites = 24, int $relax = 2, float $thickness = 1.2, int $tones = 6, int $seed = 1): self
    {
        Guard::positive($width, 'width');
        Guard::positive($height, 'height');
        Guard::atLeast($sites, 2, 'sites');
        Guard::atMost((float) $sites, 400.0, 'sites', '400');
        Guard::atLeast($relax, 0, 'relax');
        Guard::atMost((float) $relax, 12.0, 'relax', '12');
        Guard::atLeast($tones, 2, 'tones');
        Guard::atMost((float) $tones, 24.0, 'tones', '24');

        if ($thickness < 0.0 || !is_finite($thickness)) {
            throw new InvalidArgumentException(\sprintf('thickness must be a finite number of 0 or more, got %s.', var_export($thickness, true)));
        }

        return new self($width, $height, $sites, $relax, $thickness, $tones, $seed);
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
        $sites = $this->sites();

        for ($pass = 0; $pass < $this->relax; ++$pass) {
            $sites = $this->relaxed($sites);
        }

        $tones = $this->tones($sites);
        $shapes = [];

        // Fills first, strokes after: an edge is shared by two cells, and a
        // fill laid down later would eat the half of it lying on its side.
        foreach ($sites as $index => [$x, $y]) {
            $cell = $this->cellOf($x, $y, $sites);

            if (\count($cell) < 3) {
                continue;
            }

            $tone = $tones[$index];

            $shapes[] = $this->toned($this->polygon($cell), $tone, 0.12 + 0.60 * ($tone / max(1, $this->tones - 1)));
        }

        if ($this->thickness > 0.0) {
            foreach ($sites as [$x, $y]) {
                $cell = $this->cellOf($x, $y, $sites);

                if (\count($cell) < 3) {
                    continue;
                }

                $shapes[] = $this->outlined($this->polygon($cell), $this->thickness);
            }
        }

        return $shapes;
    }

    /**
     * The sites, thrown one per cell of a lattice rather than anywhere in the
     * frame: a uniform scatter leaves clumps and voids that no number of
     * relaxation passes fully undoes.
     *
     * @return list<array{float, float}>
     */
    private function sites(): array
    {
        $random = new Randomizer(new Xoshiro256StarStar($this->seed));

        $columns = max(1, (int) round(sqrt($this->sites * $this->width / $this->height)));
        $rows = max(1, (int) ceil($this->sites / $columns));

        $sites = [];

        for ($index = 0; $index < $this->sites; ++$index) {
            $column = $index % $columns;
            $row = intdiv($index, $columns);

            $sites[] = [
                ($column + $random->getFloat(0.1, 0.9)) * $this->width / $columns,
                ($row + $random->getFloat(0.1, 0.9)) * $this->height / $rows,
            ];
        }

        return $sites;
    }

    /**
     * A tone per site, drawn rather than taken from its index.
     *
     * Sites are laid out lattice first, so an index carries a position. Toning
     * by it walks the palette across the frame and the partition reads as a
     * ramp, which is the one thing a mosaic of territories should not do.
     *
     * The draw runs after the sites are placed, so asking for tones never moves
     * a cell and the partition a seed names stays the one it named.
     *
     * @param list<array{float, float}> $sites
     *
     * @return list<int>
     */
    private function tones(array $sites): array
    {
        $random = new Randomizer(new Xoshiro256StarStar($this->seed));
        $tones = [];

        foreach ($sites as $ignored) {
            $tones[] = $random->getInt(0, $this->tones - 1);
        }

        return $tones;
    }

    /**
     * One relaxation pass: move each site to the arithmetic mean of its cell vertices.
     *
     * @param list<array{float, float}> $sites
     *
     * @return list<array{float, float}>
     */
    private function relaxed(array $sites): array
    {
        $moved = [];

        foreach ($sites as [$x, $y]) {
            $cell = $this->cellOf($x, $y, $sites);

            if (\count($cell) < 3) {
                $moved[] = [$x, $y];

                continue;
            }

            $sumX = 0.0;
            $sumY = 0.0;

            foreach ($cell as [$cornerX, $cornerY]) {
                $sumX += $cornerX;
                $sumY += $cornerY;
            }

            $moved[] = [$sumX / \count($cell), $sumY / \count($cell)];
        }

        return $moved;
    }

    /**
     * The cell of one site: the frame, cut by the bisector of every pair.
     *
     * @param list<array{float, float}> $sites
     *
     * @return list<array{float, float}>
     */
    private function cellOf(float $x, float $y, array $sites): array
    {
        $cell = [[0.0, 0.0], [$this->width, 0.0], [$this->width, $this->height], [0.0, $this->height]];

        foreach ($sites as [$otherX, $otherY]) {
            $dx = $otherX - $x;
            $dy = $otherY - $y;

            if (0.0 === $dx && 0.0 === $dy) {
                continue;
            }

            // Points at least as close to (x, y) as to the other site are those
            // on this side of the bisector of the two.
            $cell = Clip::halfPlane($cell, $dx, $dy, ($dx * ($x + $otherX) + $dy * ($y + $otherY)) / 2);
        }

        return $cell;
    }

    /**
     * @param list<array{float, float}> $cell
     */
    private function polygon(array $cell): PolygonElement
    {
        $points = [];

        foreach ($cell as [$x, $y]) {
            $points[] = Num::format($x).','.Num::format($y);
        }

        $polygon = new PolygonElement();
        $polygon->setPoints(implode(' ', $points));

        return $polygon;
    }
}
