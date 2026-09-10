<?php

declare(strict_types=1);

namespace Atelier\Field\Tests\Internal;

use Atelier\Field\Internal\Grid;
use Atelier\Field\Internal\Isoline;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Isoline::class)]
final class IsolineTest extends TestCase
{
    public function testEveryCornerConfigurationProducesTheExpectedCrossings(): void
    {
        $grid = new Grid(2.0, 2.0, 2.0);
        $top = [1.0, 0.0];
        $right = [2.0, 1.0];
        $bottom = [1.0, 2.0];
        $left = [0.0, 1.0];
        $expected = [[], [[$left, $bottom]], [[$bottom, $right]], [[$left, $right]], [[$top, $right]], [[$left, $top], [$bottom, $right]], [[$top, $bottom]], [[$left, $top]], [[$left, $top]], [[$top, $bottom]], [[$left, $bottom], [$top, $right]], [[$top, $right]], [[$left, $right]], [[$bottom, $right]], [[$left, $bottom]], []];
        foreach ($expected as $case => $segments) {
            $field = [[($case & 8) ? 1.0 : 0.0, ($case & 4) ? 1.0 : 0.0], [($case & 1) ? 1.0 : 0.0, ($case & 2) ? 1.0 : 0.0]];
            self::assertSame($segments, Isoline::segments($grid, $field, 0.5), 'case '.$case);
        }
    }

    public function testCrossingsAreInterpolatedRatherThanPlacedAtTheMidpoint(): void
    {
        self::assertSame([[[0.0, 0.5], [2.0, 0.5]]], Isoline::segments(new Grid(2.0, 2.0, 2.0), [[0.0, 0.0], [4.0, 4.0]], 1.0));
    }

    public function testLoopsJoinReversedSegmentsAndDiscardOpenChains(): void
    {
        $a = [0.0, 0.0];
        $b = [2.0, 0.0];
        $c = [2.0, 2.0];
        $d = [0.0, 2.0];
        self::assertSame([[$a, $b, $c, $d, $a]], Isoline::loops([[$a, $b], [$c, $b], [$c, $d], [$a, $d], [[4.0, 4.0], [5.0, 5.0]]]));
        self::assertSame([], Isoline::loops([]));
    }
}
