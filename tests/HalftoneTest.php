<?php

declare(strict_types=1);

namespace Atelier\Field\Tests;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Field;
use Atelier\Field\Halftone;
use Atelier\Svg\Element\ElementInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Halftone::class)]
final class HalftoneTest extends TestCase
{
    private static function radius(ElementInterface $dot): float
    {
        return (float) $dot->getAttribute('r');
    }

    /**
     * @param list<ElementInterface> $dots
     */
    private static function nearest(array $dots, float $x, float $y): ElementInterface
    {
        $best = $dots[0];
        $distance = \PHP_FLOAT_MAX;

        foreach ($dots as $dot) {
            $reach = hypot((float) $dot->getAttribute('cx') - $x, (float) $dot->getAttribute('cy') - $y);

            if ($reach < $distance) {
                $distance = $reach;
                $best = $dot;
            }
        }

        return $best;
    }

    public function testTheFactoryReturnsTheAreaItWasGiven(): void
    {
        $halftone = Field::halftone(600.0, 400.0);

        self::assertSame(600.0, $halftone->width());
        self::assertSame(400.0, $halftone->height());
    }

    public function testOneDotPerCell(): void
    {
        // 600 by 400 at a spacing of 20 divides exactly: 30 columns, 20 rows.
        self::assertCount(600, Field::halftone(600.0, 400.0, spacing: 20.0)->shapes());
    }

    public function testEveryDotSitsInsideTheFrame(): void
    {
        foreach (Field::halftone(600.0, 400.0, spacing: 20.0)->shapes() as $dot) {
            $x = (float) $dot->getAttribute('cx');
            $y = (float) $dot->getAttribute('cy');

            self::assertGreaterThan(0.0, $x);
            self::assertLessThan(600.0, $x);
            self::assertGreaterThan(0.0, $y);
            self::assertLessThan(400.0, $y);
        }
    }

    public function testTheLargestDotStandsWhereTheLightIs(): void
    {
        $dots = Field::halftone(600.0, 400.0, spacing: 20.0, focusX: 0.25, focusY: 0.75)->shapes();

        $radii = array_map(self::radius(...), $dots);

        self::assertSame(max($radii), self::radius(self::nearest($dots, 150.0, 300.0)));
    }

    public function testTheDotsShrinkWithDistanceFromTheLight(): void
    {
        $dots = Field::halftone(600.0, 400.0, spacing: 20.0, focusX: 0.5, focusY: 0.5)->shapes();

        $middle = self::radius(self::nearest($dots, 300.0, 200.0));
        $edge = self::radius(self::nearest($dots, 590.0, 390.0));

        self::assertGreaterThan($edge, $middle);
    }

    public function testEveryDotHasASurface(): void
    {
        foreach (Field::halftone(600.0, 400.0, spacing: 20.0)->shapes() as $dot) {
            self::assertGreaterThan(0.0, self::radius($dot));
        }
    }

    public function testMovingTheLightMovesTheGradient(): void
    {
        self::assertNotSame(
            Field::halftone(600.0, 400.0, focusX: 0.2)->toSvg(),
            Field::halftone(600.0, 400.0, focusX: 0.8)->toSvg(),
        );
    }

    public function testTheDrawingIsTheSameEveryTime(): void
    {
        self::assertSame(
            Field::halftone(600.0, 400.0)->toSvg(),
            Field::halftone(600.0, 400.0)->toSvg(),
        );
    }

    public function testAnAreaWithNoSurfaceIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::halftone(0.0, 400.0);
    }

    public function testASpacingCoarserThanTheGuardIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::halftone(600.0, 400.0, spacing: 200.0);
    }

    public function testALightOutsideTheFrameIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::halftone(600.0, 400.0, focusX: 1.4);
    }
}
