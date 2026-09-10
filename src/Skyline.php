<?php

declare(strict_types=1);

namespace Atelier\Field;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Internal\Guard;
use Atelier\Field\Internal\Silhouette;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * A city seen from across the water: blocks of unequal width and height, shut
 * along the bottom of the frame.
 *
 * The profile is drawn block by block, each one a rise, a roof and a fall, so
 * the silhouette is made of right angles alone. Widths and heights are read as
 * fractions of the frame, so the city keeps its proportion at any size, and the
 * last block is cut at the right edge rather than allowed to run past it.
 *
 * Two ranks are drawn when the stack asks for them: the back one paler and
 * lower, which is the only depth cue a flat silhouette can carry.
 */
final class Skyline extends AbstractField
{
    /** Deepest a stack goes: the front rank sits this far down the frame. */
    private const float SPREAD = 0.22;

    /** A remainder shorter than this share of a block joins the block before it. */
    private const float MERGE = 0.6;

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
     * @param int   $layers ranks of buildings, 1 to 8
     * @param int   $seed   same seed, same drawing
     *
     * @throws InvalidArgumentException if the area is not positive or the stack is out of range
     */
    public static function create(float $width = 600.0, float $height = 400.0, int $layers = 2, int $seed = 1): self
    {
        Guard::positive($width, 'width');
        Guard::positive($height, 'height');
        Guard::atLeast($layers, 1, 'layers');
        Guard::atMost((float) $layers, 8.0, 'layers', '8');

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

            $ground = $this->height * (0.74 + self::SPREAD * $depth);
            $tallest = $this->height * (0.34 + 0.30 * $depth);

            $profile = [[0.0, $ground]];
            $x = 0.0;

            while ($x < $this->width) {
                $block = $random->getFloat(0.03, 0.09) * $this->width;
                $roof = $ground - $random->getFloat(0.28, 1.0) * $tallest;
                $right = $x + $block;

                // A block landing just short of the edge would leave a sliver
                // one unit wide, which reads as a scratch rather than as a
                // building. The last block takes the remainder instead.
                if ($this->width - $right < $block * self::MERGE) {
                    $right = $this->width;
                }

                $profile[] = [$x, $roof];
                $profile[] = [$right, $roof];

                $x = $right;
            }

            $profile[] = [$this->width, $ground];

            $band = Silhouette::band($profile, $this->width, $this->height);

            $shapes[] = $this->toned($band, $layer, Silhouette::tone($layer, $this->layers, 0.28, 0.80));
        }

        return $shapes;
    }
}
