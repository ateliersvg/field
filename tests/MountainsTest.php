<?php

declare(strict_types=1);

namespace Atelier\Field\Tests;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Field;
use Atelier\Field\Mountains;
use Atelier\Field\Tests\Support\Profile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Mountains::class)]
final class MountainsTest extends TestCase
{
    public function testTheFactoryReturnsTheAreaItWasGiven(): void
    {
        $mountains = Field::mountains(600.0, 400.0);

        self::assertSame(600.0, $mountains->width());
        self::assertSame(400.0, $mountains->height());
    }

    public function testOneRangePerLayer(): void
    {
        self::assertCount(5, Field::mountains(600.0, 400.0, layers: 5)->shapes());
    }

    public function testEveryRangeSpansTheFrameAndStaysInside(): void
    {
        foreach (Field::mountains(600.0, 400.0, layers: 4)->shapes() as $range) {
            Profile::assertSpans(Profile::of($range), 600.0, 400.0);
        }
    }

    public function testACrestAlternatesWithASaddle(): void
    {
        $profile = Profile::of(Field::mountains(600.0, 400.0, layers: 1)->shapes()[0]);

        // Odd nodes are crests, so each one stands above both its neighbours.
        // A range drawn from heights taken one after another fails this.
        for ($node = 1; $node < \count($profile) - 1; $node += 2) {
            self::assertLessThan($profile[$node - 1][1], $profile[$node][1]);
            self::assertLessThan($profile[$node + 1][1], $profile[$node][1]);
        }
    }

    public function testTheEdgesOfTheFrameAreSaddles(): void
    {
        $profile = Profile::of(Field::mountains(600.0, 400.0, layers: 1)->shapes()[0]);

        $first = $profile[0];
        $last = $profile[array_key_last($profile)];

        self::assertSame(0.0, $first[0]);
        self::assertSame(600.0, $last[0]);
        // A saddle sits lower on the screen than the crest beside it, so its
        // y is the larger of the two.
        self::assertGreaterThan($profile[1][1], $first[1]);
    }

    public function testASingleLayerTakesTheFrontTone(): void
    {
        $shapes = Field::mountains(600.0, 400.0, layers: 1)->shapes();

        self::assertSame('0.8', $shapes[0]->getAttribute('fill-opacity'));
    }

    public function testTheSameSeedDrawsTheSameField(): void
    {
        self::assertSame(
            Field::mountains(600.0, 400.0, seed: 7)->toSvg(),
            Field::mountains(600.0, 400.0, seed: 7)->toSvg(),
        );
    }

    public function testTwoSeedsDrawTwoFields(): void
    {
        self::assertNotSame(
            Field::mountains(600.0, 400.0, seed: 1)->toSvg(),
            Field::mountains(600.0, 400.0, seed: 2)->toSvg(),
        );
    }

    public function testAnAreaWithNoSurfaceIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::mountains(0.0, 400.0);
    }

    public function testAStackWithNoRangeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::mountains(600.0, 400.0, layers: 0);
    }

    public function testAStackDeeperThanTheGuardIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::mountains(600.0, 400.0, layers: 25);
    }
}
