<?php

declare(strict_types=1);

namespace Atelier\Field\Internal;

use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * Value noise on a permutation table, the height source the contour, flow and
 * terrain fields read.
 *
 * The table is one shuffle of 0..255 doubled, so a lookup never needs a bounds
 * test. A seed fixes the shuffle, and therefore the whole landscape.
 *
 * @internal
 */
final class Noise
{
    /**
     * Opens one octave up to [-1, 1]. Measured rather than derived: the bound a
     * corner could reach is not the bound the interpolation between four of
     * them actually reaches.
     */
    private const float GAIN = 2.0;

    /** @var list<int> */
    private array $permutation;

    public function __construct(int $seed)
    {
        $values = range(0, 255);
        $random = new Randomizer(new Xoshiro256StarStar($seed));

        for ($index = 255; $index > 0; --$index) {
            $swap = $random->getInt(0, $index);
            [$values[$index], $values[$swap]] = [$values[$swap], $values[$index]];
        }

        $this->permutation = [...$values, ...$values];
    }

    /**
     * Height at a point, in [-1, 1]. Octaves stack halved amplitudes at doubled
     * frequencies, which is what turns one smooth swell into terrain.
     *
     * Stacking narrows the spread: two octaves rarely leave [-0.8, 0.8],
     * because the second is unlikely to peak where the first does. A caller
     * wanting the full range from a stack has to open it up itself.
     */
    public function at(float $x, float $y, int $octaves = 1): float
    {
        $sum = 0.0;
        $amplitude = 1.0;
        $frequency = 1.0;
        $total = 0.0;

        for ($octave = 0; $octave < $octaves; ++$octave) {
            $sum += $amplitude * $this->single($x * $frequency, $y * $frequency);
            $total += $amplitude;
            $amplitude *= 0.5;
            $frequency *= 2.0;
        }

        return $sum / $total;
    }

    private function single(float $x, float $y): float
    {
        $cellX = ((int) floor($x)) & 255;
        $cellY = ((int) floor($y)) & 255;
        $fractionX = $x - floor($x);
        $fractionY = $y - floor($y);
        $easeX = self::fade($fractionX);
        $easeY = self::fade($fractionY);

        $topLeft = $this->permutation[$this->permutation[$cellX] + $cellY];
        $bottomLeft = $this->permutation[$this->permutation[$cellX] + $cellY + 1];
        $topRight = $this->permutation[$this->permutation[$cellX + 1] + $cellY];
        $bottomRight = $this->permutation[$this->permutation[$cellX + 1] + $cellY + 1];

        return self::lerp(
            self::lerp(self::gradient($topLeft, $fractionX, $fractionY), self::gradient($topRight, $fractionX - 1.0, $fractionY), $easeX),
            self::lerp(self::gradient($bottomLeft, $fractionX, $fractionY - 1.0), self::gradient($bottomRight, $fractionX - 1.0, $fractionY - 1.0), $easeX),
            $easeY,
        );
    }

    private static function fade(float $t): float
    {
        return $t * $t * $t * ($t * ($t * 6.0 - 15.0) + 10.0);
    }

    private static function lerp(float $from, float $to, float $at): float
    {
        return $from + ($to - $from) * $at;
    }

    /**
     * The contribution of one lattice corner, opened up to the range the field
     * promises.
     *
     * The two offsets of a corner cancel as often as they add, so the
     * interpolated field only ever reaches half of what a single corner does.
     * Measured over a long walk, one octave lands in [-0.5, 0.5] without this
     * factor, and a caller reading a height in [-1, 1] silently gets half the
     * relief it asked for.
     */
    private static function gradient(int $hash, float $x, float $y): float
    {
        return ((0 === ($hash & 1) ? $x : -$x) + (0 === ($hash & 2) ? $y : -$y)) * self::GAIN;
    }
}
