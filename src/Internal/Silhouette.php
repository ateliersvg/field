<?php

declare(strict_types=1);

namespace Atelier\Field\Internal;

use Atelier\Svg\Element\PathElement;

/**
 * A profile closed to the bottom of the frame.
 *
 * Every landscape in this package is one line read left to right and then shut
 * along the bottom edge, so it paints as a mass. Waves, dunes and mountains
 * differ in how they compute that line, never in how they close it.
 *
 * @internal
 */
final class Silhouette
{
    /**
     * @param list<array{float, float}> $profile the crest, left to right
     */
    public static function band(array $profile, float $width, float $height): PathElement
    {
        $path = '';

        foreach ($profile as $index => [$x, $y]) {
            $path .= (0 === $index ? 'M' : 'L').Num::format($x).' '.Num::format($y);
        }

        $path .= 'L'.Num::format($width).' '.Num::format($height)
            .'L0 '.Num::format($height).'Z';

        $band = new PathElement();
        $band->setD($path);

        return $band;
    }

    /**
     * Positions to sample a profile at, always including both edges so a band
     * reaches the sides of the frame instead of stopping short of them.
     *
     * @return list<float>
     */
    public static function samples(float $width, float $step): array
    {
        $count = max(2, (int) ceil($width / $step));
        $samples = [];

        for ($sample = 0; $sample <= $count; ++$sample) {
            $samples[] = $width * $sample / $count;
        }

        return $samples;
    }

    /**
     * Where a layer sits in its stack, 0 at the back and 1 at the front.
     *
     * A lone layer sits in the middle. With nothing behind it there is no
     * distance to state, and the middle is where one silhouette has both room
     * to rise and ground to close against.
     */
    public static function depth(int $layer, int $layers): float
    {
        return $layers > 1 ? $layer / ($layers - 1) : 0.5;
    }

    /**
     * Opacity of a layer, from the back of the stack to the front.
     *
     * A lone layer takes the front value rather than the one its position would
     * give it. Fading is how a stack states distance, and a single band fading
     * against nothing is only a pale band: the first render of layers: 1 came
     * out at 0.16 and was barely there.
     */
    public static function tone(int $layer, int $layers, float $back, float $front): float
    {
        return $layers > 1
            ? $back + ($front - $back) * self::depth($layer, $layers)
            : $front;
    }
}
