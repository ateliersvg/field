<?php

declare(strict_types=1);

namespace Atelier\Field;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Internal\Guard;
use Atelier\Field\Internal\Noise;
use Atelier\Field\Internal\Num;
use Atelier\Svg\Element\PathElement;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * Lines traced through a field of directions, the way iron filings lie along a
 * magnet.
 *
 * A noise field gives an angle at every point. A line starts somewhere and
 * walks, turning to whatever the field says at each step, until it leaves the
 * frame or runs out of length. Neighbouring lines read the same angles, so they
 * travel together without ever being told to.
 *
 * Starts are spread one per cell of a lattice rather than thrown anywhere. A
 * uniform scatter leaves bald patches on a frame this size, and a bald patch on
 * a drawing whose whole subject is a current reads as a mistake.
 */
final class FlowField extends AbstractField
{
    /** Distance walked between two turns, as a share of the smaller side. */
    private const float STEP = 0.012;

    private function __construct(
        private readonly float $width,
        private readonly float $height,
        private readonly int $lines,
        private readonly int $length,
        private readonly float $thickness,
        private readonly int $seed,
    ) {
    }

    /**
     * @param float $width     area to cover
     * @param float $height    area to cover
     * @param int   $lines     starts spread over the frame, 1 to 2000
     * @param int   $length    steps a line walks before it stops, 2 to 400
     * @param float $thickness line width
     * @param int   $seed      same seed, same current
     *
     * @throws InvalidArgumentException if the area is not positive or a count is out of range
     */
    public static function create(float $width = 600.0, float $height = 400.0, int $lines = 260, int $length = 60, float $thickness = 1.0, int $seed = 1): self
    {
        Guard::positive($width, 'width');
        Guard::positive($height, 'height');
        Guard::atLeast($lines, 1, 'lines');
        Guard::atMost((float) $lines, 2000.0, 'lines', '2000');
        Guard::atLeast($length, 2, 'length');
        Guard::atMost((float) $length, 400.0, 'length', '400');
        Guard::positive($thickness, 'thickness');

        return new self($width, $height, $lines, $length, $thickness, $seed);
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
        $random = new Randomizer(new Xoshiro256StarStar($this->seed));
        $step = min($this->width, $this->height) * self::STEP;

        $columns = max(1, (int) round(sqrt($this->lines * $this->width / $this->height)));
        $rows = max(1, (int) ceil($this->lines / $columns));

        $shapes = [];

        for ($index = 0; $index < $this->lines; ++$index) {
            $column = $index % $columns;
            $row = intdiv($index, $columns);

            $x = ($column + $random->getFloat(0.05, 0.95)) * $this->width / $columns;
            $y = ($row + $random->getFloat(0.05, 0.95)) * $this->height / $rows;

            $path = $this->trace($noise, $x, $y, $step);

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
     * One line, walked until it leaves the frame or runs out of length.
     *
     * A line stopping on the border is kept as it is: clipping it would be the
     * same drawing, and letting it run past would put ink outside the area the
     * field was asked to cover.
     */
    private function trace(Noise $noise, float $x, float $y, float $step): string
    {
        $path = 'M'.Num::format($x).' '.Num::format($y);
        $drawn = 0;

        for ($walked = 0; $walked < $this->length; ++$walked) {
            $angle = $noise->at($x / $this->width * 2.4, $y / $this->height * 2.4, 2) * M_PI;

            $x += cos($angle) * $step;
            $y += sin($angle) * $step;

            if ($x < 0.0 || $x > $this->width || $y < 0.0 || $y > $this->height) {
                break;
            }

            $path .= 'L'.Num::format($x).' '.Num::format($y);
            ++$drawn;
        }

        // A start that leaves at its first step is a dot, and a dot in a field
        // of currents reads as dirt.
        return $drawn < 2 ? '' : $path;
    }
}
