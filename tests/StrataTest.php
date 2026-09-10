<?php

declare(strict_types=1);

namespace Atelier\Field\Tests;

use Atelier\Field\AbstractField;
use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Field;
use Atelier\Field\Strata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Strata::class)]
#[CoversClass(AbstractField::class)]
#[CoversClass(Field::class)]
final class StrataTest extends TestCase
{
    public function testTheFactoryReturnsTheAreaItWasGiven(): void
    {
        $strata = Field::strata(600.0, 400.0);

        self::assertSame(600.0, $strata->width());
        self::assertSame(400.0, $strata->height());
    }

    public function testOneShapePerBed(): void
    {
        self::assertCount(11, Field::strata(600.0, 400.0, beds: 11)->shapes());
    }

    /**
     * Boundaries are summed rather than drawn one by one, which is what keeps
     * two of them from crossing however the thicknesses vary.
     */
    public function testNoBedEverCrossesTheOneBelowIt(): void
    {
        foreach (Field::strata(600.0, 400.0, beds: 8, seed: 4)->shapes() as $bed) {
            $points = $this->pointsOf($bed);
            $half = \count($points) / 2;

            for ($sample = 0; $sample < $half; ++$sample) {
                $top = $points[$sample];
                $bottom = $points[\count($points) - 1 - $sample];

                self::assertSame($top[0], $bottom[0], 'The two boundaries are sampled at the same places.');
                self::assertLessThanOrEqual($bottom[1] + 0.0001, $top[1]);
            }
        }
    }

    public function testTheSectionIsCutByTheFrameRatherThanFloatingInside(): void
    {
        $shapes = Field::strata(600.0, 400.0, beds: 6)->shapes();

        $first = $this->pointsOf($shapes[0]);
        $last = $this->pointsOf($shapes[5]);

        self::assertLessThan(0.0, min(array_column($first, 1)), 'The first bed opens above the frame.');
        self::assertGreaterThan(400.0, max(array_column($last, 1)), 'The last bed closes below it.');
    }

    public function testTheSameSeedDrawsTheSameSection(): void
    {
        self::assertSame(
            Field::strata(600.0, 400.0, seed: 9)->toSvg(),
            Field::strata(600.0, 400.0, seed: 9)->toSvg(),
        );
    }

    public function testTwoSeedsDrawTwoSections(): void
    {
        self::assertNotSame(
            Field::strata(600.0, 400.0, seed: 1)->toSvg(),
            Field::strata(600.0, 400.0, seed: 2)->toSvg(),
        );
    }

    public function testASectionOfOneBedIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::strata(600.0, 400.0, beds: 1);
    }

    public function testADipSteeperThanTheGuardIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::strata(600.0, 400.0, dip: 40.0);
    }

    /**
     * @return list<array{float, float}>
     */
    private function pointsOf(object $bed): array
    {
        preg_match_all('/-?\d+(?:\.\d+)?/', (string) $bed->getAttribute('d'), $matches);

        $points = [];

        foreach (array_chunk(array_map(floatval(...), $matches[0]), 2) as $pair) {
            $points[] = [$pair[0], $pair[1]];
        }

        return $points;
    }
}
