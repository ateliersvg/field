<?php

declare(strict_types=1);

namespace Atelier\Field\Tests;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Field;
use Atelier\Field\Moire;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Moire::class)]
final class MoireTest extends TestCase
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
        $moire = Field::moire(600.0, 400.0);

        self::assertSame(600.0, $moire->width());
        self::assertSame(400.0, $moire->height());
    }

    public function testTwoRulingsAndNoMore(): void
    {
        self::assertCount(2, Field::moire(600.0, 400.0)->shapes());
    }

    public function testTheTwoRulingsAreNotTheSame(): void
    {
        $shapes = Field::moire(600.0, 400.0)->shapes();

        self::assertNotSame(
            (string) $shapes[0]->getAttribute('d'),
            (string) $shapes[1]->getAttribute('d'),
        );
    }

    public function testEveryLineStaysInsideTheFrame(): void
    {
        foreach (Field::moire(600.0, 400.0)->shapes() as $ruling) {
            foreach (self::points((string) $ruling->getAttribute('d')) as [$x, $y]) {
                self::assertGreaterThanOrEqual(-0.001, $x);
                self::assertLessThanOrEqual(600.001, $x);
                self::assertGreaterThanOrEqual(-0.001, $y);
                self::assertLessThanOrEqual(400.001, $y);
            }
        }
    }

    public function testARulingReachesBothEdges(): void
    {
        $points = self::points((string) Field::moire(600.0, 400.0)->shapes()[0]->getAttribute('d'));

        $left = min(array_column($points, 0));
        $right = max(array_column($points, 0));

        // A ruling carries an arbitrary phase, so no line lands exactly on an
        // edge. What has to hold is that the gutter never reaches a whole
        // spacing, which is what dropping the overshooting line used to leave.
        self::assertLessThan(5.0, $left);
        self::assertGreaterThan(595.0, $right);
    }

    public function testTheLineWidthFollowsTheSpacing(): void
    {
        $width = static fn (float $spacing): float => (float) Field::moire(600.0, 400.0, spacing: $spacing, duty: 0.4)
            ->shapes()[0]
            ->getAttribute('stroke-width');

        // The beat is a change in coverage, so a line is a share of its spacing
        // rather than a width of its own.
        self::assertEqualsWithDelta(2.0, $width(5.0), 0.001);
        self::assertEqualsWithDelta(4.0, $width(10.0), 0.001);
    }

    public function testTheDrawingIsTheSameEveryTime(): void
    {
        self::assertSame(
            Field::moire(600.0, 400.0)->toSvg(),
            Field::moire(600.0, 400.0)->toSvg(),
        );
    }

    public function testAnAngleTooOpenToBeatIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::moire(600.0, 400.0, angle: 25.0);
    }

    public function testASpacingCoarserThanTheGuardIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::moire(600.0, 400.0, spacing: 200.0);
    }

    public function testADutyPastFullCoverageIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::moire(600.0, 400.0, duty: 1.4);
    }
}
