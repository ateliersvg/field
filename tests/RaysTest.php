<?php

declare(strict_types=1);

namespace Atelier\Field\Tests;

use Atelier\Field\AbstractField;
use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Field;
use Atelier\Field\Rays;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Rays::class)]
#[CoversClass(AbstractField::class)]
#[CoversClass(Field::class)]
final class RaysTest extends TestCase
{
    public function testTheFactoryReturnsTheAreaItWasGiven(): void
    {
        $rays = Field::rays(600.0, 400.0);

        self::assertSame(600.0, $rays->width());
        self::assertSame(400.0, $rays->height());
    }

    public function testACentreInsideTheFrameIsReachedByEveryWedge(): void
    {
        self::assertCount(16, Field::rays(600.0, 400.0, rays: 16, focusY: 0.5)->shapes());
    }

    /**
     * A centre outside the frame turns half the fan away from it, and the
     * wedges that miss are dropped rather than drawn empty.
     */
    public function testACentreOutsideTheFrameDropsTheWedgesThatMissIt(): void
    {
        $wedges = Field::rays(600.0, 400.0, rays: 16, focusY: 1.4)->shapes();

        self::assertGreaterThan(0, \count($wedges));
        self::assertLessThan(16, \count($wedges));
    }

    public function testEveryWedgeIsCutToTheFrame(): void
    {
        foreach (Field::rays(600.0, 400.0)->shapes() as $wedge) {
            foreach ($this->cornersOf($wedge) as [$x, $y]) {
                self::assertGreaterThanOrEqual(-0.01, $x);
                self::assertLessThanOrEqual(600.01, $x);
                self::assertGreaterThanOrEqual(-0.01, $y);
                self::assertLessThanOrEqual(400.01, $y);
            }
        }
    }

    public function testAWiderDutyCoversMoreOfTheSurface(): void
    {
        self::assertGreaterThan(
            $this->inkOf(Field::rays(600.0, 400.0, duty: 0.3)),
            $this->inkOf(Field::rays(600.0, 400.0, duty: 0.7)),
        );
    }

    public function testTheFieldTakesNoSeed(): void
    {
        self::assertSame(
            Field::rays(600.0, 400.0)->toSvg(),
            Field::rays(600.0, 400.0)->toSvg(),
        );
    }

    public function testAFanOfOneWedgeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::rays(600.0, 400.0, rays: 1);
    }

    public function testACentreFarOutsideTheFrameIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::rays(600.0, 400.0, focusY: 3.0);
    }

    /**
     * Area of every wedge, by the shoelace formula.
     */
    private function inkOf(Rays $rays): float
    {
        $ink = 0.0;

        foreach ($rays->shapes() as $wedge) {
            $corners = $this->cornersOf($wedge);
            $count = \count($corners);
            $sum = 0.0;

            for ($corner = 0; $corner < $count; ++$corner) {
                [$x1, $y1] = $corners[$corner];
                [$x2, $y2] = $corners[($corner + 1) % $count];
                $sum += $x1 * $y2 - $x2 * $y1;
            }

            $ink += abs($sum) / 2;
        }

        return $ink;
    }

    /**
     * @return list<array{float, float}>
     */
    private function cornersOf(object $wedge): array
    {
        $corners = [];

        foreach (explode(' ', (string) $wedge->getAttribute('points')) as $pair) {
            [$x, $y] = explode(',', $pair);
            $corners[] = [(float) $x, (float) $y];
        }

        return $corners;
    }
}
