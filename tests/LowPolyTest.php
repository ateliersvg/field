<?php

declare(strict_types=1);

namespace Atelier\Field\Tests;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Field;
use Atelier\Field\LowPoly;
use Atelier\Svg\Element\ElementInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LowPoly::class)]
final class LowPolyTest extends TestCase
{
    /**
     * @return list<array{float, float}>
     */
    private static function corners(ElementInterface $facet): array
    {
        $points = [];

        foreach (explode(' ', (string) $facet->getAttribute('points')) as $pair) {
            [$x, $y] = explode(',', $pair);

            $points[] = [(float) $x, (float) $y];
        }

        return $points;
    }

    public function testTheFactoryReturnsTheAreaItWasGiven(): void
    {
        $lowPoly = Field::lowPoly(600.0, 400.0);

        self::assertSame(600.0, $lowPoly->width());
        self::assertSame(400.0, $lowPoly->height());
    }

    public function testTwoTrianglesPerCell(): void
    {
        // 600 by 400 at a cell of 100 divides exactly: 6 columns, 4 rows.
        self::assertCount(48, Field::lowPoly(600.0, 400.0, cell: 100.0)->shapes());
    }

    public function testEveryCornerIsThreeAndStaysInsideTheFrame(): void
    {
        foreach (Field::lowPoly(600.0, 400.0, cell: 100.0)->shapes() as $facet) {
            $corners = self::corners($facet);

            self::assertCount(3, $corners);

            foreach ($corners as [$x, $y]) {
                self::assertGreaterThanOrEqual(0.0, $x);
                self::assertLessThanOrEqual(600.0, $x);
                self::assertGreaterThanOrEqual(0.0, $y);
                self::assertLessThanOrEqual(400.0, $y);
            }
        }
    }

    public function testTheTrianglesCoverTheFrame(): void
    {
        $area = 0.0;

        foreach (Field::lowPoly(600.0, 400.0, cell: 100.0)->shapes() as $facet) {
            [$a, $b, $c] = self::corners($facet);

            $area += abs(($b[0] - $a[0]) * ($c[1] - $a[1]) - ($c[0] - $a[0]) * ($b[1] - $a[1])) / 2;
        }

        // The cut is a partition, so the triangles add up to the frame exactly.
        // A jitter leaking a node outside its own cell would overlap a
        // neighbour and push this over.
        self::assertEqualsWithDelta(600.0 * 400.0, $area, 1.0);
    }

    public function testTheCutDoesNotReadAsOneHatch(): void
    {
        $facets = Field::lowPoly(600.0, 400.0, cell: 100.0)->shapes();

        // Triangles come in pairs, one cell each. The edge a pair shares is the
        // diagonal, and its direction is what turns with the parity of the
        // cell. One direction everywhere is graph paper with a hatch on it.
        $directions = [];

        for ($pair = 0; $pair < \count($facets); $pair += 2) {
            $shared = [];

            foreach (self::corners($facets[$pair]) as $corner) {
                foreach (self::corners($facets[$pair + 1]) as $other) {
                    if (abs($corner[0] - $other[0]) < 0.001 && abs($corner[1] - $other[1]) < 0.001) {
                        $shared[] = $corner;
                    }
                }
            }

            self::assertCount(2, $shared, 'Two triangles of one cell share their diagonal.');

            $directions[] = ($shared[1][0] - $shared[0][0]) * ($shared[1][1] - $shared[0][1]) > 0;
        }

        self::assertContains(true, $directions);
        self::assertContains(false, $directions);
    }

    public function testTheSameSeedDrawsTheSameCut(): void
    {
        self::assertSame(
            Field::lowPoly(600.0, 400.0, seed: 7)->toSvg(),
            Field::lowPoly(600.0, 400.0, seed: 7)->toSvg(),
        );
    }

    public function testTwoSeedsDrawTwoCuts(): void
    {
        self::assertNotSame(
            Field::lowPoly(600.0, 400.0, seed: 1)->toSvg(),
            Field::lowPoly(600.0, 400.0, seed: 2)->toSvg(),
        );
    }

    public function testAnAreaWithNoSurfaceIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::lowPoly(600.0, 0.0);
    }

    public function testACellCoarserThanTheGuardIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::lowPoly(600.0, 400.0, cell: 400.0);
    }

    public function testASingleToneIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::lowPoly(600.0, 400.0, tones: 1);
    }
}
