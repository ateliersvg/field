<?php

declare(strict_types=1);

namespace Atelier\Field;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Internal\Guard;
use Atelier\Field\Internal\Silhouette;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * Ridge lines of straight segments, each closed to the bottom of the frame.
 *
 * A crest alternates with a saddle, so the line rises and falls once per pair
 * instead of wandering: heights drawn one after another with no such rule read
 * as a sawtooth, not as a range. Both edges of the frame are saddles, which is
 * what keeps a peak from being cut in half by the border.
 *
 * Every height is a fraction of the frame, so a range keeps its proportion at
 * any size. Ranges further back carry more peaks and sit higher, the two things
 * distance does to a skyline of rock.
 */
final class Mountains extends AbstractField
{
    /** Deepest a stack goes: the front range sits this far down the frame. */
    private const float SPREAD = 0.34;

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
     * @param int   $layers ranges in the stack, 1 to 24
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
        $random = new Randomizer(new Xoshiro256StarStar($this->seed));
        $shapes = [];

        for ($layer = 0; $layer < $this->layers; ++$layer) {
            $depth = Silhouette::depth($layer, $this->layers);

            $baseline = $this->height * (0.46 + self::SPREAD * $depth);
            $reach = $this->height * (0.30 - 0.12 * $depth);

            // The far ranges are the busy ones. Distance packs peaks together,
            // and the near range reads as a few large masses instead.
            $peaks = 3 + ($this->layers - $layer);
            $profile = [];

            $nodes = 2 * $peaks;
            $step = $this->width / $nodes;

            for ($node = 0; $node <= $nodes; ++$node) {
                $x = $step * $node;

                // Evenly spaced nodes give a metronome, so every node but the
                // two on the edges shifts inside its own half step. The order
                // never changes, which is what keeps the segments from crossing.
                if ($node > 0 && $node < $nodes) {
                    $x += $random->getFloat(-0.36, 0.36) * $step;
                }

                // Even nodes are saddles, odd nodes are crests. The first and
                // the last are therefore saddles, on the edges of the frame.
                $rise = 0 === $node % 2
                    ? $random->getFloat(0.0, 0.28)
                    : $random->getFloat(0.55, 1.0);

                $profile[] = [$x, $baseline - $rise * $reach];
            }

            $band = Silhouette::band($profile, $this->width, $this->height);

            $shapes[] = $this->toned($band, $layer, Silhouette::tone($layer, $this->layers, 0.16, 0.80));
        }

        return $shapes;
    }
}
