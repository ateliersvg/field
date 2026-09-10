<?php

declare(strict_types=1);

namespace Atelier\Field\Tests\Internal;

use Atelier\Field\Internal\Clip;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Clip::class)]
final class ClipTest extends TestCase
{
    public function testSegmentsAreClippedToTheFrame(): void
    {
        self::assertSame([0.0, 5.0, 10.0, 5.0], Clip::segment(-5.0, 5.0, 15.0, 5.0, 10.0, 10.0));
        self::assertSame([5.0, 0.0, 5.0, 10.0], Clip::segment(5.0, -5.0, 5.0, 15.0, 10.0, 10.0));
        self::assertSame([2.0, 2.0, 8.0, 8.0], Clip::segment(2.0, 2.0, 8.0, 8.0, 10.0, 10.0));
        self::assertNull(Clip::segment(-5.0, -1.0, 15.0, -1.0, 10.0, 10.0));
        self::assertNull(Clip::segment(-5.0, 1.0, 1.0, -5.0, 10.0, 10.0));
    }

    public function testRunsInterpolateCrossingsAndSplitOutsideExcursions(): void
    {
        self::assertSame([
            [[0.0, 2.0], [5.0, 2.0], [10.0, 2.0]],
            [[10.0, 8.0], [5.0, 8.0], [0.0, 8.0]],
        ], Clip::runs([[-5.0, 2.0], [5.0, 2.0], [15.0, 2.0], [15.0, 8.0], [5.0, 8.0], [-5.0, 8.0]], 10.0, 10.0));
        self::assertSame([[[0.0, 5.0], [10.0, 5.0]]], Clip::runs([[-5.0, 5.0], [15.0, 5.0]], 10.0, 10.0));
        self::assertSame([
            [[5.0, 5.0], [10.0, 5.0]],
            [[10.0, 7.5], [5.0, 10.0]],
        ], Clip::runs([[5.0, 5.0], [15.0, 5.0], [5.0, 10.0]], 10.0, 10.0));
    }

    public function testRunsDiscardTangenciesAndDuplicatePointsWithoutBreakingAValidRun(): void
    {
        self::assertSame([], Clip::runs([[-1.0, 1.0], [1.0, -1.0]], 10.0, 10.0));
        self::assertSame([[[2.0, 2.0], [5.0, 5.0], [8.0, 8.0]]], Clip::runs([[2.0, 2.0], [5.0, 5.0], [5.0, 5.0], [8.0, 8.0]], 10.0, 10.0));
        self::assertSame([], Clip::runs([[-5.0, -5.0], [-2.0, -2.0]], 10.0, 10.0));
        self::assertSame([], Clip::runs([[5.0, 5.0]], 10.0, 10.0));
        self::assertSame([], Clip::runs([], 10.0, 10.0));
    }

    public function testHalfPlaneKeepsTheCorrectSideAndInterpolatesIntersections(): void
    {
        self::assertSame([[0.0, 0.0], [5.0, 0.0], [5.0, 10.0], [0.0, 10.0]], Clip::halfPlane([[0.0, 0.0], [10.0, 0.0], [10.0, 10.0], [0.0, 10.0]], 1.0, 0.0, 5.0));
        self::assertSame([], Clip::halfPlane([], 1.0, 0.0, 5.0));
    }

    public function testPolygonClippingPreservesTheFrameAndRejectsDisjointPolygons(): void
    {
        $clipped = Clip::polygon([[-5.0, -5.0], [15.0, -5.0], [15.0, 15.0], [-5.0, 15.0]], 10.0, 10.0);
        self::assertEqualsCanonicalizing([[0.0, 0.0], [10.0, 0.0], [10.0, 10.0], [0.0, 10.0]], $clipped);
        self::assertSame([], Clip::polygon([[-5.0, -5.0], [-1.0, -5.0], [-1.0, -1.0]], 10.0, 10.0));
    }
}
