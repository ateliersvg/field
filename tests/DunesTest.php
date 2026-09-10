<?php

declare(strict_types=1);

namespace Atelier\Field\Tests;

use Atelier\Field\Dunes;
use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Field;
use Atelier\Field\Tests\Support\Profile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Dunes::class)]
final class DunesTest extends TestCase
{
    public function testTheFactoryReturnsTheAreaItWasGiven(): void
    {
        $dunes = Field::dunes(600.0, 400.0);

        self::assertSame(600.0, $dunes->width());
        self::assertSame(400.0, $dunes->height());
    }

    public function testOneRidgePerLayer(): void
    {
        self::assertCount(6, Field::dunes(600.0, 400.0, layers: 6)->shapes());
    }

    public function testEveryRidgeSpansTheFrameAndStaysInside(): void
    {
        foreach (Field::dunes(600.0, 400.0, layers: 5)->shapes() as $ridge) {
            Profile::assertSpans(Profile::of($ridge), 600.0, 400.0);
        }
    }

    public function testTwoRidgesAreNotParallel(): void
    {
        $shapes = Field::dunes(600.0, 400.0, layers: 4)->shapes();

        $behind = Profile::of($shapes[1]);
        $front = Profile::of($shapes[2]);

        $gaps = [];

        foreach ($behind as $index => [, $y]) {
            $gaps[] = $front[$index][1] - $y;
        }

        $mean = array_sum($gaps) / \count($gaps);
        $swing = (max($gaps) - min($gaps)) / $mean;

        // A stack of ridges holding a constant gap reads as parallel bands, and
        // that is the whole difference between a dune field and a set of
        // stripes. Half the mean gap is the swing a relief needs to be read.
        self::assertGreaterThan(0.5, $swing, 'Two ridges must not hold a constant gap.');
    }

    public function testTwoLayersDoNotShareTheSameCrest(): void
    {
        $shapes = Field::dunes(600.0, 400.0, layers: 3)->shapes();

        self::assertNotSame(
            array_column(Profile::of($shapes[0]), 1),
            array_column(Profile::of($shapes[1]), 1),
        );
    }

    public function testASingleLayerTakesTheFrontTone(): void
    {
        $shapes = Field::dunes(600.0, 400.0, layers: 1)->shapes();

        self::assertSame('0.8', $shapes[0]->getAttribute('fill-opacity'));
    }

    public function testTheSameSeedDrawsTheSameField(): void
    {
        self::assertSame(
            Field::dunes(600.0, 400.0, seed: 7)->toSvg(),
            Field::dunes(600.0, 400.0, seed: 7)->toSvg(),
        );
    }

    public function testTwoSeedsDrawTwoFields(): void
    {
        self::assertNotSame(
            Field::dunes(600.0, 400.0, seed: 1)->toSvg(),
            Field::dunes(600.0, 400.0, seed: 2)->toSvg(),
        );
    }

    public function testAnAreaWithNoSurfaceIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::dunes(600.0, 0.0);
    }

    public function testAStackWithNoRidgeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::dunes(600.0, 400.0, layers: 0);
    }

    public function testAStackDeeperThanTheGuardIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::dunes(600.0, 400.0, layers: 25);
    }
}
