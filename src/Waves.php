<?php

declare(strict_types=1);

namespace Atelier\Field;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Internal\Guard;
use Atelier\Field\Internal\Silhouette;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * Layered sine bands, each closed to the bottom of the frame.
 *
 * One band is a single sine sampled across the width and shut along the bottom
 * edge, so it paints as a mass rather than as a line. Layers walk their
 * baselines down the frame and deepen as they come forward, which is what gives
 * the stack its depth.
 *
 * Amplitude is a fraction of the height and frequency is counted over the
 * width, so a band keeps its proportion and its number of crests whatever the
 * frame measures.
 */
final class Waves extends AbstractField
{
    /** Distance between two samples of a curve, in user units. */
    private const float SAMPLE = 8.0;

    /** Deepest a stack goes: the front band sits this far down the frame. */
    private const float SPREAD = 0.45;

    private function __construct(
        private readonly float $width,
        private readonly float $height,
        private readonly int $layers,
        private readonly int $seed,
    ) {
    }

    /**
     * @param float $width  area to cover
     * @param float $height area to cover
     * @param int   $layers bands in the stack, 1 to 24
     * @param int   $seed   same seed, same drawing
     *
     * @throws InvalidArgumentException if the area is not positive or the stack is out of range
     */
    public static function create(float $width = 600.0, float $height = 400.0, int $layers = 5, int $seed = 1): self
    {
        Guard::positive($width, 'width');
        Guard::positive($height, 'height');
        Guard::atLeast($layers, 1, 'layers');
        Guard::atMost((float) $layers, 24.0, 'layers', '24');

        return new self($width, $height, $layers, $seed);
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
        $random = new Randomizer(new Xoshiro256StarStar($this->seed));
        $samples = Silhouette::samples($this->width, self::SAMPLE);
        $shapes = [];

        for ($layer = 0; $layer < $this->layers; ++$layer) {
            // Every draw happens before the band is built, so the sequence a
            // seed produces never depends on the geometry it is spent on.
            $amplitude = $random->getFloat(0.03, 0.09) * $this->height;
            $frequency = $random->getFloat(1.4, 3.4);
            $phase = $random->getFloat(0.0, 2 * M_PI);

            $depth = Silhouette::depth($layer, $this->layers);
            $baseline = $this->height * (0.40 + self::SPREAD * $depth);

            $profile = [];

            foreach ($samples as $x) {
                $profile[] = [$x, $baseline + sin($x / $this->width * 2 * M_PI * $frequency + $phase) * $amplitude];
            }

            $band = Silhouette::band($profile, $this->width, $this->height);

            $shapes[] = $this->toned($band, $layer, Silhouette::tone($layer, $this->layers, 0.18, 0.80));
        }

        return $shapes;
    }
}
