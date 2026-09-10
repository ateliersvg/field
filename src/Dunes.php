<?php

declare(strict_types=1);

namespace Atelier\Field;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Internal\Guard;
use Atelier\Field\Internal\Noise;
use Atelier\Field\Internal\Silhouette;

/**
 * Ridges of sand, each closed to the bottom of the frame.
 *
 * A dune has no period. Its crest is read from a noise field rather than from a
 * sine, so no two rises along one ridge are alike and the line still never
 * breaks: value noise is continuous, which a sequence of random heights is not.
 *
 * Each layer reads its own row of the field, far enough apart that two ridges
 * never echo one another. Rows are walked at a fixed step rather than drawn at
 * random, so adding a layer leaves the ones behind it exactly where they were.
 *
 * The crest is smooth and the trough is flat: sand piles into a rounded back
 * and settles on a long apron, and a symmetric wave reads as water instead.
 */
final class Dunes extends AbstractField
{
    /** Distance between two samples of a crest, in user units. */
    private const float SAMPLE = 6.0;

    /** Crests along one ridge. Lower stretches the rises, higher crowds them. */
    private const float ROUGHNESS = 3.4;

    /** Distance between two layers in the noise field, in field units. */
    private const float ROW_STEP = 11.0;

    /** Deepest a stack goes: the front ridge sits this far down the frame. */
    private const float SPREAD = 0.50;

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
     * @param int   $layers ridges in the stack, 1 to 24
     * @param int   $seed   same seed, same drawing
     *
     * @throws InvalidArgumentException if the area is not positive or the stack is out of range
     */
    public static function create(float $width = 600.0, float $height = 400.0, int $layers = 4, int $seed = 1): self
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
        $noise = new Noise($this->seed);
        $samples = Silhouette::samples($this->width, self::SAMPLE);
        $shapes = [];

        for ($layer = 0; $layer < $this->layers; ++$layer) {
            $depth = Silhouette::depth($layer, $this->layers);

            // The near ridges are the tall ones, which is what puts the horizon
            // at the top of the frame rather than in the middle of it.
            //
            // Amplitude is read against the gap between two baselines: below it
            // the ridges never meet and the stack reads as parallel bands, so a
            // crest has to be tall enough to rise past the ridge behind it.
            $amplitude = $this->height * (0.13 + 0.09 * $depth);
            $baseline = $this->height * (0.30 + self::SPREAD * $depth);

            // Nearer ridges are read at a lower frequency, which is what
            // distance does to a dune field: the far ones crowd together.
            $roughness = self::ROUGHNESS * (1.0 - 0.28 * $depth);
            $row = $layer * self::ROW_STEP;

            $profile = [];

            foreach ($samples as $x) {
                $ridge = $noise->at($x / $this->width * $roughness, $row, 2);

                $profile[] = [$x, $baseline - self::crest($ridge) * $amplitude];
            }

            $band = Silhouette::band($profile, $this->width, $this->height);

            $shapes[] = $this->toned($band, $layer, Silhouette::tone($layer, $this->layers, 0.20, 0.80));
        }

        return $shapes;
    }

    /**
     * The asymmetry of sand: a value in [-1, 1] becomes a height in [0, 1] whose
     * rises are rounded and whose hollows are long and shallow.
     */
    private static function crest(float $value): float
    {
        $height = ($value + 1.0) / 2.0;

        return $height * $height * (3.0 - 2.0 * $height);
    }
}
