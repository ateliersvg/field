<?php

declare(strict_types=1);

namespace Atelier\Field\Tests;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Field;
use Atelier\Field\FlowField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FlowField::class)]
final class FlowFieldTest extends TestCase
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
        $flow = Field::flowField(600.0, 400.0);

        self::assertSame(600.0, $flow->width());
        self::assertSame(400.0, $flow->height());
    }

    public function testMostStartsBecomeALine(): void
    {
        // A start leaving the frame at its first step is dropped, so the count
        // is at most what was asked for and should be close to it.
        $lines = Field::flowField(600.0, 400.0, lines: 200)->shapes();

        self::assertLessThanOrEqual(200, \count($lines));
        self::assertGreaterThan(150, \count($lines));
    }

    public function testEveryLineHasLength(): void
    {
        foreach (Field::flowField(600.0, 400.0, lines: 60)->shapes() as $line) {
            self::assertGreaterThanOrEqual(3, \count(self::points((string) $line->getAttribute('d'))));
        }
    }

    public function testEveryPointStaysInsideTheFrame(): void
    {
        foreach (Field::flowField(600.0, 400.0, lines: 60)->shapes() as $line) {
            foreach (self::points((string) $line->getAttribute('d')) as [$x, $y]) {
                self::assertGreaterThanOrEqual(0.0, $x);
                self::assertLessThanOrEqual(600.0, $x);
                self::assertGreaterThanOrEqual(0.0, $y);
                self::assertLessThanOrEqual(400.0, $y);
            }
        }
    }

    public function testTheStartsCoverTheWholeFrame(): void
    {
        $quadrants = [false, false, false, false];

        foreach (Field::flowField(600.0, 400.0, lines: 200)->shapes() as $line) {
            [$x, $y] = self::points((string) $line->getAttribute('d'))[0];

            $quadrants[($x > 300.0 ? 1 : 0) + ($y > 200.0 ? 2 : 0)] = true;
        }

        // Starts are spread one per cell of a lattice. A uniform scatter leaves
        // bald patches, and a bald patch in a field of currents reads as a
        // mistake.
        self::assertSame([true, true, true, true], $quadrants);
    }

    public function testTheSameSeedDrawsTheSameCurrent(): void
    {
        self::assertSame(
            Field::flowField(600.0, 400.0, seed: 7)->toSvg(),
            Field::flowField(600.0, 400.0, seed: 7)->toSvg(),
        );
    }

    public function testTwoSeedsDrawTwoCurrents(): void
    {
        self::assertNotSame(
            Field::flowField(600.0, 400.0, seed: 1)->toSvg(),
            Field::flowField(600.0, 400.0, seed: 2)->toSvg(),
        );
    }

    public function testAnAreaWithNoSurfaceIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::flowField(600.0, 0.0);
    }

    public function testALineWithNoLengthIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::flowField(600.0, 400.0, length: 1);
    }

    public function testMoreLinesThanTheGuardIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::flowField(600.0, 400.0, lines: 2001);
    }
}
