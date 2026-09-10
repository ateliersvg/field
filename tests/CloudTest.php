<?php

declare(strict_types=1);

namespace Atelier\Field\Tests;

use Atelier\Field\Cloud;
use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Field;
use Atelier\Field\Tests\Support\Profile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Cloud::class)]
final class CloudTest extends TestCase
{
    public function testTheFactoryReturnsTheAreaItWasGiven(): void
    {
        $cloud = Field::cloud(400.0, 240.0);

        self::assertSame(400.0, $cloud->width());
        self::assertSame(240.0, $cloud->height());
    }

    public function testOneOutline(): void
    {
        self::assertCount(1, Field::cloud(400.0, 240.0)->shapes());
    }

    public function testTheOutlineSpansTheFrameAndStaysInside(): void
    {
        $profile = Profile::of(Field::cloud(400.0, 240.0)->shapes()[0]);

        Profile::assertSpans($profile, 400.0, 240.0);
    }

    public function testTheRowMergesIntoOneMass(): void
    {
        $profile = Profile::of(Field::cloud(400.0, 240.0, puffs: 6)->shapes()[0]);

        // The outline is the upper envelope of overlapping discs, so between
        // the first and the last puff it never comes back down to the base. A
        // notch there would mean two discs had come apart.
        $inside = \array_slice($profile, (int) (\count($profile) * 0.3), (int) (\count($profile) * 0.4));

        foreach ($inside as [, $y]) {
            self::assertLessThan(240.0, $y);
        }
    }

    public function testTheEndsAreLowerThanTheMiddle(): void
    {
        $profile = Profile::of(Field::cloud(400.0, 240.0, puffs: 6)->shapes()[0]);
        $heights = array_column($profile, 1);

        $middle = $heights[(int) (\count($heights) / 2)];

        // A cloud rising straight off the ground at both ends reads as a wall.
        self::assertLessThan($heights[2], $middle);
        self::assertLessThan($heights[\count($heights) - 3], $middle);
    }

    public function testTheSameSeedDrawsTheSameCloud(): void
    {
        self::assertSame(
            Field::cloud(400.0, 240.0, seed: 7)->toSvg(),
            Field::cloud(400.0, 240.0, seed: 7)->toSvg(),
        );
    }

    public function testTwoSeedsDrawTwoClouds(): void
    {
        self::assertNotSame(
            Field::cloud(400.0, 240.0, seed: 1)->toSvg(),
            Field::cloud(400.0, 240.0, seed: 2)->toSvg(),
        );
    }

    public function testFewerThanTwoPuffsIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::cloud(400.0, 240.0, puffs: 1);
    }

    public function testMorePuffsThanTheGuardIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::cloud(400.0, 240.0, puffs: 17);
    }

    public function testAnAreaWithNoSurfaceIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::cloud(400.0, 0.0);
    }
}
