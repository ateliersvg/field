<?php

declare(strict_types=1);

namespace Atelier\Field\Tests\Support;

use Atelier\Svg\Element\ElementInterface;
use PHPUnit\Framework\Assert;

/**
 * Reads back the crest of a band from the path it was drawn as.
 *
 * A landscape is a profile shut along the bottom edge, so its `d` ends with the
 * two corners of the frame. Dropping them leaves the line the generator
 * computed, which is what these tests are about.
 */
final class Profile
{
    /**
     * @return list<array{float, float}>
     */
    public static function of(ElementInterface $band): array
    {
        $d = (string) $band->getAttribute('d');

        Assert::assertMatchesRegularExpression('/Z$/', $d, 'A band must be closed.');

        preg_match_all('/[ML](-?[\d.]+) (-?[\d.]+)/', $d, $matches, \PREG_SET_ORDER);

        $points = [];

        foreach ($matches as $point) {
            $points[] = [(float) $point[1], (float) $point[2]];
        }

        // The last two are the corners the band was shut with.
        return \array_slice($points, 0, -2);
    }

    /**
     * Asserts a profile spans the frame, stays inside it, and runs one way.
     *
     * @param list<array{float, float}> $profile
     */
    public static function assertSpans(array $profile, float $width, float $height): void
    {
        Assert::assertNotEmpty($profile);
        Assert::assertSame(0.0, $profile[0][0], 'A profile must start on the left edge.');
        Assert::assertSame($width, $profile[array_key_last($profile)][0], 'A profile must end on the right edge.');

        $previous = -1.0;

        foreach ($profile as [$x, $y]) {
            Assert::assertGreaterThanOrEqual($previous, $x, 'A profile must not double back.');
            Assert::assertGreaterThanOrEqual(0.0, $y, 'A profile must stay inside the frame.');
            Assert::assertLessThanOrEqual($height, $y, 'A profile must stay inside the frame.');

            $previous = $x;
        }
    }
}
