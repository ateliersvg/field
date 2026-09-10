<?php

declare(strict_types=1);

namespace Atelier\Field\Tests\Internal;

use Atelier\Field\Internal\Noise;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Noise::class)]
final class NoiseTest extends TestCase
{
    /**
     * @return list<float>
     */
    private static function walk(Noise $noise, int $octaves): array
    {
        $values = [];

        for ($step = 0; $step < 4000; ++$step) {
            $values[] = $noise->at($step * 0.037, ($step % 7) * 11.0, $octaves);
        }

        return $values;
    }

    public function testOneOctaveSpansTheRangeItPromises(): void
    {
        $values = self::walk(new Noise(1), 1);

        self::assertGreaterThan(0.9, max($values));
        self::assertLessThan(-0.9, min($values));
        self::assertLessThanOrEqual(1.0, max($values));
        self::assertGreaterThanOrEqual(-1.0, min($values));
    }

    public function testStackingOctavesNarrowsTheSpreadWithoutLeavingTheRange(): void
    {
        $values = self::walk(new Noise(1), 4);

        self::assertLessThanOrEqual(1.0, max($values));
        self::assertGreaterThanOrEqual(-1.0, min($values));
        self::assertLessThan(max(self::walk(new Noise(1), 1)), max($values));
    }

    public function testTheFieldIsCentredOnZero(): void
    {
        $values = self::walk(new Noise(1), 2);

        self::assertEqualsWithDelta(0.0, array_sum($values) / count($values), 0.05);
    }

    public function testTheFieldIsContinuous(): void
    {
        $noise = new Noise(1);
        $previous = $noise->at(0.0, 0.0);

        // A step far smaller than a lattice cell cannot move the height much.
        // A sequence of independent draws would fail this at the first step.
        for ($step = 1; $step <= 500; ++$step) {
            $value = $noise->at($step * 0.004, 0.0);

            self::assertLessThan(0.05, abs($value - $previous));

            $previous = $value;
        }
    }

    public function testTheSameSeedGivesTheSameField(): void
    {
        self::assertSame(self::walk(new Noise(7), 2), self::walk(new Noise(7), 2));
    }

    public function testTwoSeedsGiveTwoFields(): void
    {
        self::assertNotSame(self::walk(new Noise(1), 2), self::walk(new Noise(2), 2));
    }
}
