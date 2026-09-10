<?php

declare(strict_types=1);

namespace Atelier\Field\Tests;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Field;
use Atelier\Field\Voronoi;
use Atelier\Svg\Element\ElementInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Voronoi::class)]
final class VoronoiTest extends TestCase
{
    /**
     * @return list<array{float, float}>
     */
    private static function corners(ElementInterface $cell): array
    {
        $points = [];

        foreach (explode(' ', (string) $cell->getAttribute('points')) as $pair) {
            [$x, $y] = explode(',', $pair);

            $points[] = [(float) $x, (float) $y];
        }

        return $points;
    }

    /**
     * @param list<array{float, float}> $corners
     */
    private static function area(array $corners): float
    {
        $sum = 0.0;
        $count = \count($corners);

        for ($corner = 0; $corner < $count; ++$corner) {
            [$x1, $y1] = $corners[$corner];
            [$x2, $y2] = $corners[($corner + 1) % $count];

            $sum += $x1 * $y2 - $x2 * $y1;
        }

        return abs($sum) / 2;
    }

    public function testNumericallyCollapsedCellsAreOmittedFromFillsAndStrokes(): void
    {
        // Squared distances overflow at this scale. A collapsed polygon must
        // not become an invalid SVG polygon, including after relaxation.
        $field = Field::voronoi(1e160, 1e160, sites: 2, relax: 1);

        self::assertSame([], $field->shapes());
        self::assertStringNotContainsString('NAN', $field->toSvg());
        self::assertStringNotContainsString('INF', $field->toSvg());
    }

    public function testTheFactoryReturnsTheAreaItWasGiven(): void
    {
        $voronoi = Field::voronoi(600.0, 400.0);

        self::assertSame(600.0, $voronoi->width());
        self::assertSame(400.0, $voronoi->height());
    }

    public function testOneFillAndOneStrokePerCell(): void
    {
        self::assertCount(40, Field::voronoi(600.0, 400.0, sites: 20)->shapes());
    }

    public function testCellsAreLeftUnstrokedAtNoThickness(): void
    {
        self::assertCount(20, Field::voronoi(600.0, 400.0, sites: 20, thickness: 0.0)->shapes());
    }

    public function testTheCellsCoverTheFrameExactly(): void
    {
        $shapes = Field::voronoi(600.0, 400.0, sites: 20, thickness: 0.0)->shapes();

        $area = 0.0;

        foreach ($shapes as $cell) {
            $area += self::area(self::corners($cell));
        }

        // A partition, so the cells add up to the frame and never overlap.
        self::assertEqualsWithDelta(600.0 * 400.0, $area, 1.0);
    }

    public function testEveryCornerStaysInsideTheFrame(): void
    {
        foreach (Field::voronoi(600.0, 400.0, sites: 20, thickness: 0.0)->shapes() as $cell) {
            foreach (self::corners($cell) as [$x, $y]) {
                self::assertGreaterThanOrEqual(-0.001, $x);
                self::assertLessThanOrEqual(600.001, $x);
                self::assertGreaterThanOrEqual(-0.001, $y);
                self::assertLessThanOrEqual(400.001, $y);
            }
        }
    }

    public function testRelaxingEvensTheCellsOut(): void
    {
        $spread = static function (int $relax): float {
            $areas = [];

            foreach (Field::voronoi(600.0, 400.0, sites: 30, relax: $relax, thickness: 0.0)->shapes() as $cell) {
                $areas[] = self::area(self::corners($cell));
            }

            return (max($areas) - min($areas)) / (array_sum($areas) / \count($areas));
        };

        // Lloyd moves each site onto the middle of its own cell, which is what
        // turns a scatter with clumps and voids into a mesh of comparable cells.
        self::assertLessThan($spread(0), $spread(4));
    }

    public function testTheSameSeedDrawsTheSamePartition(): void
    {
        self::assertSame(
            Field::voronoi(600.0, 400.0, seed: 7)->toSvg(),
            Field::voronoi(600.0, 400.0, seed: 7)->toSvg(),
        );
    }

    public function testTwoSeedsDrawTwoPartitions(): void
    {
        self::assertNotSame(
            Field::voronoi(600.0, 400.0, seed: 1)->toSvg(),
            Field::voronoi(600.0, 400.0, seed: 2)->toSvg(),
        );
    }

    public function testASingleSiteIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::voronoi(600.0, 400.0, sites: 1);
    }

    public function testANegativeThicknessIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::voronoi(600.0, 400.0, thickness: -1.0);
    }

    public function testMoreRelaxationThanTheGuardIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::voronoi(600.0, 400.0, relax: 13);
    }
}
