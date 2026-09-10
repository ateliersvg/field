<?php

declare(strict_types=1);

namespace Atelier\Field;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Internal\Guard;
use Atelier\Field\Internal\Noise;
use Atelier\Field\Internal\Num;
use Atelier\Field\Internal\Silhouette;
use Atelier\Svg\Element\PathElement;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * A section through sedimentary rock: beds of unequal thickness, dipping across
 * the frame.
 *
 * Every boundary is the sum of the thicknesses above it, and a thickness varies
 * along its own length. Summing rather than drawing each boundary on its own is
 * what keeps two of them from crossing, and letting a thickness fall to nothing
 * is what lets a bed pinch out the way a real one does.
 *
 * The first boundary sits above the frame and the last below it, so the section
 * is cut by the frame rather than floating inside it.
 */
final class Strata extends AbstractField
{
    /** How far a thickness may leave its own mean, as a share of it. */
    private const float VARIATION = 0.55;

    /** Distance between two samples of a boundary. */
    private const float SAMPLE = 6.0;

    private function __construct(
        private readonly float $width,
        private readonly float $height,
        private readonly int $beds,
        private readonly float $dip,
        private readonly int $seed,
    ) {
    }

    /**
     * @param float $width  area to cover
     * @param float $height area to cover
     * @param int   $beds   beds in the section, 2 to 24
     * @param float $dip    tilt of the whole section, in degrees, at most 30
     * @param int   $seed   same seed, same section
     *
     * @throws InvalidArgumentException if the area is not positive or a count is out of range
     */
    public static function create(float $width = 600.0, float $height = 400.0, int $beds = 8, float $dip = 5.0, int $seed = 1): self
    {
        Guard::positive($width, 'width');
        Guard::positive($height, 'height');
        Guard::atLeast($beds, 2, 'beds');
        Guard::atMost((float) $beds, 24.0, 'beds', '24');
        Guard::between($dip, -30.0, 30.0, 'dip');

        return new self($width, $height, $beds, $dip, $seed);
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

        $weights = [];
        $total = 0.0;

        for ($bed = 0; $bed < $this->beds; ++$bed) {
            $weights[$bed] = $random->getFloat(0.5, 1.6);
            $total += $weights[$bed];
        }

        $samples = Silhouette::samples($this->width, self::SAMPLE);
        $slope = tan(deg2rad($this->dip));

        $boundary = function (int $index) use ($weights, $total, $noise, $slope, $samples): array {
            $line = [];

            foreach ($samples as $x) {
                if (0 === $index) {
                    $line[] = [$x, -$this->height];

                    continue;
                }

                if ($index === $this->beds) {
                    $line[] = [$x, 2 * $this->height];

                    continue;
                }

                $y = 0.0;

                for ($above = 0; $above < $index; ++$above) {
                    $thickness = $weights[$above] / $total * $this->height;
                    $y += $thickness * (1.0 + self::VARIATION * $noise->at($x * 0.007, $above * 1.7));
                }

                $line[] = [$x, $y + $slope * ($x - $this->width / 2)];
            }

            return $line;
        };

        $shapes = [];

        for ($bed = 0; $bed < $this->beds; ++$bed) {
            $path = new PathElement();
            $path->setD($this->band($boundary($bed), $boundary($bed + 1)));

            $shapes[] = $this->toned($path, $bed, Silhouette::tone($bed, $this->beds, 0.22, 0.85));
        }

        return $shapes;
    }

    /**
     * One bed, closed between the boundary above it and the one below.
     *
     * @param list<array{float, float}> $top
     * @param list<array{float, float}> $bottom
     */
    private function band(array $top, array $bottom): string
    {
        $path = '';

        foreach ($top as $index => [$x, $y]) {
            $path .= (0 === $index ? 'M' : 'L').Num::format($x).' '.Num::format($y);
        }

        foreach (array_reverse($bottom) as [$x, $y]) {
            $path .= 'L'.Num::format($x).' '.Num::format($y);
        }

        return $path.'Z';
    }
}
