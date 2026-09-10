<?php

declare(strict_types=1);

namespace Atelier\Field\Tests;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Field;
use Atelier\Field\Skyline;
use Atelier\Field\Tests\Support\Profile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Skyline::class)]
final class SkylineTest extends TestCase
{
    public function testTheFactoryReturnsTheAreaItWasGiven(): void
    {
        $skyline = Field::skyline(600.0, 400.0);

        self::assertSame(600.0, $skyline->width());
        self::assertSame(400.0, $skyline->height());
    }

    public function testOneSilhouettePerRank(): void
    {
        self::assertCount(3, Field::skyline(600.0, 400.0, layers: 3)->shapes());
    }

    public function testEveryRankSpansTheFrameAndStaysInside(): void
    {
        foreach (Field::skyline(600.0, 400.0, layers: 3)->shapes() as $rank) {
            Profile::assertSpans(Profile::of($rank), 600.0, 400.0);
        }
    }

    public function testNoBlockIsASliver(): void
    {
        // A block landing one unit short of the right edge used to leave a
        // scratch rather than a building. The remainder joins the block instead.
        foreach (Field::skyline(600.0, 400.0, layers: 4)->shapes() as $rank) {
            $profile = Profile::of($rank);

            // The profile opens on the ground at the left edge, so roofs are
            // the pairs after it.
            for ($node = 1; $node < \count($profile) - 1; $node += 2) {
                $roof = $profile[$node + 1][0] - $profile[$node][0];

                self::assertGreaterThan(1.0, $roof, 'A roof must be wide enough to read as a building.');
            }
        }
    }

    public function testTheSilhouetteIsMadeOfRightAnglesAlone(): void
    {
        $profile = Profile::of(Field::skyline(600.0, 400.0, layers: 1)->shapes()[0]);

        // Nodes come in pairs sharing a height: a roof is level, and the rise
        // between two roofs is vertical.
        for ($node = 1; $node < \count($profile) - 1; $node += 2) {
            self::assertSame($profile[$node][1], $profile[$node + 1][1], 'A roof must be level.');
        }
    }

    public function testASingleLayerTakesTheFrontTone(): void
    {
        $shapes = Field::skyline(600.0, 400.0, layers: 1)->shapes();

        self::assertSame('0.8', $shapes[0]->getAttribute('fill-opacity'));
    }

    public function testTheSameSeedDrawsTheSameField(): void
    {
        self::assertSame(
            Field::skyline(600.0, 400.0, seed: 7)->toSvg(),
            Field::skyline(600.0, 400.0, seed: 7)->toSvg(),
        );
    }

    public function testTwoSeedsDrawTwoFields(): void
    {
        self::assertNotSame(
            Field::skyline(600.0, 400.0, seed: 1)->toSvg(),
            Field::skyline(600.0, 400.0, seed: 2)->toSvg(),
        );
    }

    public function testAnAreaWithNoSurfaceIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::skyline(600.0, 0.0);
    }

    public function testAStackWithNoRankIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::skyline(600.0, 400.0, layers: 0);
    }

    public function testAStackDeeperThanTheGuardIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::skyline(600.0, 400.0, layers: 9);
    }
}
