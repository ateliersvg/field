<?php

declare(strict_types=1);

namespace Atelier\Field;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Internal\Guard;
use Atelier\Field\Internal\Num;
use Atelier\Svg\Element\Shape\CircleElement;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * A bounded sequence of disc-packing attempts, with radii limited by
 * neighbours, the frame and a focal gradient.
 *
 * A disc is thrown at random, grown until it meets its nearest neighbour or an
 * edge, and kept if it is still worth drawing. Later discs take the gaps the
 * earlier ones left, which is what gives the size distribution its long tail.
 *
 * The target size falls away from one point, and that gradient is what makes
 * this a field: halftone varies a radius on a lattice, and here the lattice is
 * gone as well.
 */
final class Foam extends AbstractField
{
    /** Smallest disc worth drawing, as a share of the largest. */
    private const float SPECK = 0.2;

    /** Space kept between two discs. */
    private const float MARGIN = 1.1;

    /** Throws per disc the frame could hold, which decides how full it gets. */
    private const int PERSISTENCE = 24;

    private function __construct(
        private readonly float $width,
        private readonly float $height,
        private readonly float $radius,
        private readonly float $focusX,
        private readonly float $focusY,
        private readonly int $tones,
        private readonly int $seed,
    ) {
    }

    /**
     * @param float $width  area to cover
     * @param float $height area to cover
     * @param float $radius radius of the largest disc, at most a fifth of the smaller side
     * @param float $focusX where the largest discs stand across the frame, [0, 1)
     * @param float $focusY where the largest discs stand down the frame, [0, 1)
     * @param int   $tones  tones the discs are shaded with, 2 to 24
     * @param int   $seed   same seed, same packing
     *
     * @throws InvalidArgumentException if the area is not positive or a count is out of range
     */
    public static function create(float $width = 600.0, float $height = 400.0, float $radius = 22.0, float $focusX = 0.3, float $focusY = 0.28, int $tones = 5, int $seed = 1): self
    {
        Guard::positive($width, 'width');
        Guard::positive($height, 'height');
        Guard::positive($radius, 'radius');
        Guard::atMost($radius, min($width, $height) / 5, 'radius', 'a fifth of the smaller side');
        Guard::fraction($focusX, 'focusX');
        Guard::fraction($focusY, 'focusY');
        Guard::atLeast($tones, 2, 'tones');
        Guard::atMost((float) $tones, 24.0, 'tones', '24');

        return new self($width, $height, $radius, $focusX, $focusY, $tones, $seed);
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

        $lightX = $this->width * $this->focusX;
        $lightY = $this->height * $this->focusY;
        $reach = hypot(max($lightX, $this->width - $lightX), max($lightY, $this->height - $lightY));

        $speck = $this->radius * self::SPECK;

        // Throws scale with how many of the largest disc the frame could hold,
        // so a frame twice the area is filled to the same density.
        $throws = (int) round(self::PERSISTENCE * $this->width * $this->height / ($this->radius ** 2));

        $discs = [];

        for ($throw = 0; $throw < $throws; ++$throw) {
            $x = $random->getFloat(0.0, $this->width);
            $y = $random->getFloat(0.0, $this->height);

            $near = 1.0 - hypot($x - $lightX, $y - $lightY) / $reach;
            $room = min($x, $y, $this->width - $x, $this->height - $y);

            foreach ($discs as [$otherX, $otherY, $otherRadius]) {
                $room = min($room, hypot($x - $otherX, $y - $otherY) - $otherRadius - self::MARGIN);

                if ($room < $speck) {
                    break;
                }
            }

            $radius = min($speck + ($this->radius - $speck) * $near * $near, $room);

            if ($radius >= $speck) {
                $discs[] = [$x, $y, $radius];
            }
        }

        $shapes = [];

        foreach ($discs as [$x, $y, $radius]) {
            $disc = new CircleElement();
            $disc->setCx(Num::format($x));
            $disc->setCy(Num::format($y));
            $disc->setR(Num::format($radius));

            // Tone by size, so the gradient reads even where the discs run
            // small and a palette has something to walk along.
            $tone = (int) floor(($radius - $speck) / ($this->radius - $speck) * ($this->tones - 1) + 0.5);

            $shapes[] = $this->toned($disc, $tone, 0.26 + 0.58 * ($tone / max(1, $this->tones - 1)));
        }

        return $shapes;
    }
}
