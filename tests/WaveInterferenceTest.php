<?php

declare(strict_types=1);

namespace Atelier\Field\Tests;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Field;
use Atelier\Field\WaveInterference;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WaveInterference::class)]
final class WaveInterferenceTest extends TestCase
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
        $rings = Field::waveInterference(600.0, 400.0);

        self::assertSame(600.0, $rings->width());
        self::assertSame(400.0, $rings->height());
    }

    public function testMoreSourcesDrawMoreRings(): void
    {
        self::assertGreaterThan(
            \count(Field::waveInterference(600.0, 400.0, sources: 1)->shapes()),
            \count(Field::waveInterference(600.0, 400.0, sources: 3)->shapes()),
        );
    }

    public function testEveryArcStaysInsideTheFrame(): void
    {
        foreach (Field::waveInterference(600.0, 400.0)->shapes() as $arc) {
            foreach (self::points((string) $arc->getAttribute('d')) as [$x, $y]) {
                self::assertGreaterThanOrEqual(0.0, $x);
                self::assertLessThanOrEqual(600.0, $x);
                self::assertGreaterThanOrEqual(0.0, $y);
                self::assertLessThanOrEqual(400.0, $y);
            }
        }
    }

    public function testARingReachesTheFarCorner(): void
    {
        $shapes = Field::waveInterference(600.0, 400.0, sources: 1)->shapes();

        $span = 0.0;

        foreach ($shapes as $arc) {
            foreach (self::points((string) $arc->getAttribute('d')) as [$x, $y]) {
                $span = max($span, hypot($x, $y));
            }
        }

        // Rings run out to the far corner, so a source near an edge still
        // reaches the other side rather than fading into a disc.
        self::assertGreaterThan(600.0, $span);
    }

    public function testTheSameSeedStandsTheSourcesInTheSamePlace(): void
    {
        self::assertSame(
            Field::waveInterference(600.0, 400.0, seed: 7)->toSvg(),
            Field::waveInterference(600.0, 400.0, seed: 7)->toSvg(),
        );
    }

    public function testTwoSeedsStandThemElsewhere(): void
    {
        self::assertNotSame(
            Field::waveInterference(600.0, 400.0, seed: 1)->toSvg(),
            Field::waveInterference(600.0, 400.0, seed: 2)->toSvg(),
        );
    }

    public function testAnAreaWithNoSurfaceIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::waveInterference(0.0, 400.0);
    }

    public function testNoSourceIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::waveInterference(600.0, 400.0, sources: 0);
    }

    public function testNumericallyEmptyRingsDoNotCreateEmptyPaths(): void
    {
        // At this scale the outer ring can only touch the frame after clipping.
        // This checks the fallback, not visible geometry below serialization precision.
        $shapes = Field::waveInterference(1e-300, 8.836749410992796e-301, sources: 1, spacing: 1.88767214348359e-301)->shapes();
        self::assertNotEmpty($shapes);
        foreach ($shapes as $shape) {
            self::assertNotSame('', $shape->getAttribute('d'));
        }
    }

    public function testASpacingCoarserThanTheGuardIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::waveInterference(600.0, 400.0, spacing: 200.0);
    }
}
