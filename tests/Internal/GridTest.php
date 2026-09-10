<?php

declare(strict_types=1);

namespace Atelier\Field\Tests\Internal;

use Atelier\Field\Internal\Grid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Grid::class)]
final class GridTest extends TestCase
{
    public function testSamplesReachBothEdgesWithEvenSpacing(): void
    {
        $grid = new Grid(105.0, 52.0, 10.0);
        self::assertSame(11, $grid->columns);
        self::assertSame(5, $grid->rows);
        self::assertSame(0.0, $grid->x(0));
        self::assertSame(0.0, $grid->y(0));
        self::assertEqualsWithDelta(105.0, $grid->x($grid->columns), 1e-10);
        self::assertEqualsWithDelta(52.0, $grid->y($grid->rows), 1e-10);
        self::assertSame($grid->stepX, $grid->x(1));
        self::assertSame($grid->stepY, $grid->y(1));
    }

    public function testAnOversizedCellStillHasFourCorners(): void
    {
        $grid = new Grid(2.0, 3.0, 100.0);
        self::assertSame(1, $grid->columns);
        self::assertSame(1, $grid->rows);
        self::assertSame(2.0, $grid->x(1));
        self::assertSame(3.0, $grid->y(1));
    }
}
