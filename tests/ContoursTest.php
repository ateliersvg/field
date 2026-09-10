<?php

declare(strict_types=1);

namespace Atelier\Field\Tests;

use Atelier\Field\Contours;
use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Field;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Contours::class)]
final class ContoursTest extends TestCase
{
    /**
     * @return list<array{float, float}>
     */
    private static function points(string $d): array
    {
        preg_match_all('/[ML](-?[\d.]+) (-?[\d.]+)/', $d, $matches, \PREG_SET_ORDER);

        $points = [];

        foreach ($matches as $point) {
            $points[] = [(float) $point[1], (float) $point[2]];
        }

        return $points;
    }

    public function testTheFactoryReturnsTheAreaItWasGiven(): void
    {
        $contours = Field::contours(600.0, 400.0);

        self::assertSame(600.0, $contours->width());
        self::assertSame(400.0, $contours->height());
    }

    public function testOnePathPerLevel(): void
    {
        self::assertCount(8, Field::contours(600.0, 400.0, levels: 8)->shapes());
    }

    public function testEveryLevelIsDrawn(): void
    {
        foreach (Field::contours(600.0, 400.0)->shapes() as $level) {
            self::assertNotSame('', (string) $level->getAttribute('d'));
        }
    }

    public function testEveryPointStaysInsideTheFrame(): void
    {
        foreach (Field::contours(600.0, 400.0)->shapes() as $level) {
            foreach (self::points((string) $level->getAttribute('d')) as [$x, $y]) {
                self::assertGreaterThanOrEqual(0.0, $x);
                self::assertLessThanOrEqual(600.0, $x);
                self::assertGreaterThanOrEqual(0.0, $y);
                self::assertLessThanOrEqual(400.0, $y);
            }
        }
    }

    public function testCrossingsAreInterpolatedRatherThanTakenAtTheEdgeMidpoint(): void
    {
        $cell = 20.0;
        $points = [];

        foreach (Field::contours(600.0, 400.0, cell: $cell)->shapes() as $level) {
            foreach (self::points((string) $level->getAttribute('d')) as $point) {
                $points[] = $point;
            }
        }

        $onLattice = static fn (float $value): bool => abs(fmod($value + $cell / 2, $cell) - $cell / 2) < 0.01;
        $onHalf = static fn (float $value): bool => abs(fmod($value, $cell) - $cell / 2) < 0.01;

        // A crossing lies on a lattice line one way and somewhere along the
        // edge the other way. Taking the middle of that edge instead is what
        // gives an isoline its staircase, so the free coordinate landing on a
        // half step is the signature to count.
        $staircase = 0;

        foreach ($points as [$x, $y]) {
            if (($onLattice($x) && $onHalf($y)) || ($onLattice($y) && $onHalf($x))) {
                ++$staircase;
            }
        }

        self::assertLessThan(\count($points) / 50, $staircase);
    }

    public function testTheSameSeedDrawsTheSameRelief(): void
    {
        self::assertSame(
            Field::contours(600.0, 400.0, seed: 7)->toSvg(),
            Field::contours(600.0, 400.0, seed: 7)->toSvg(),
        );
    }

    public function testTwoSeedsDrawTwoReliefs(): void
    {
        self::assertNotSame(
            Field::contours(600.0, 400.0, seed: 1)->toSvg(),
            Field::contours(600.0, 400.0, seed: 2)->toSvg(),
        );
    }

    public function testAnAreaWithNoSurfaceIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::contours(0.0, 400.0);
    }

    public function testACellCoarserThanTheGuardIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::contours(600.0, 400.0, cell: 200.0);
    }

    public function testAStackWithNoLevelIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::contours(600.0, 400.0, levels: 0);
    }
}
