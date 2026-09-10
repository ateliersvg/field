<?php

declare(strict_types=1);

namespace Atelier\Field;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Internal\Guard;
use Atelier\Field\Internal\Silhouette;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * The shape a cloud is drawn as: a flat underside and a row of puffs above it.
 *
 * The outline is the upper envelope of a row of overlapping discs, sampled
 * across the width. Where two discs overlap the taller one wins, so the row
 * merges into one mass and no notch opens between two puffs.
 *
 * Drawing the discs themselves would be quicker and would show every seam
 * between them the moment the fill is anything but opaque, which is exactly
 * what a theme with an opacity does. Chaining arcs instead leaves a scallop at
 * every join, and a scalloped top reads as a wave.
 *
 * Puff centres are spaced evenly and their radii drawn, so the row keeps its
 * span and still reads as unequal.
 */
final class Cloud extends AbstractField
{
    /** Distance between two samples of the envelope, in user units. */
    private const float SAMPLE = 3.0;

    /** How far the tallest puff may rise, as a share of the height. */
    private const float RISE = 0.94;

    private function __construct(
        private readonly float $width,
        private readonly float $height,
        private readonly int $puffs,
        private readonly int $seed,
    ) {
    }

    /**
     * @param float $width  area to cover
     * @param float $height area to cover
     * @param int   $puffs  discs the outline is built from, 2 to 16
     * @param int   $seed   same seed, same cloud
     *
     * @throws InvalidArgumentException if the area is not positive or a count is out of range
     */
    public static function create(float $width = 400.0, float $height = 240.0, int $puffs = 5, int $seed = 1): self
    {
        Guard::positive($width, 'width');
        Guard::positive($height, 'height');
        Guard::atLeast($puffs, 2, 'puffs');
        Guard::atMost((float) $puffs, 16.0, 'puffs', '16');

        return new self($width, $height, $puffs, $seed);
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
        $discs = $this->discs();
        $profile = [];

        foreach (Silhouette::samples($this->width, self::SAMPLE) as $x) {
            $profile[] = [$x, $this->height - $this->envelope($discs, $x)];
        }

        return [$this->painted(Silhouette::band($profile, $this->width, $this->height))];
    }

    /**
     * The discs the cloud is the union of, as centre and radius.
     *
     * Radii overlap their neighbours by construction: a disc spans more than
     * the gap between two centres, so the row can never come apart.
     *
     * @return list<array{float, float}>
     */
    private function discs(): array
    {
        $random = new Randomizer(new Xoshiro256StarStar($this->seed));

        $span = $this->width / ($this->puffs + 1);
        $tallest = $this->height * self::RISE;
        $discs = [];

        for ($puff = 1; $puff <= $this->puffs; ++$puff) {
            $size = $random->getFloat(0.58, 1.0);

            // The end puffs are the low ones, so the mass tapers instead of
            // rising straight off the ground at both edges.
            $shoulder = 0.42 + 0.58 * sin(M_PI * $puff / ($this->puffs + 1));

            $discs[] = [$puff * $span, min($tallest, $span * 1.05 * $size * $shoulder * 1.9)];
        }

        return $discs;
    }

    /**
     * How high the union of the discs stands above the base at one position.
     *
     * @param list<array{float, float}> $discs
     */
    private function envelope(array $discs, float $x): float
    {
        $height = 0.0;

        foreach ($discs as [$centre, $radius]) {
            $reach = $radius * $radius - ($x - $centre) * ($x - $centre);

            if ($reach > 0.0) {
                $height = max($height, sqrt($reach));
            }
        }

        return $height;
    }
}
