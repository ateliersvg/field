<?php

declare(strict_types=1);

namespace Atelier\Field\Tests;

use Atelier\Field\AbstractField;
use Atelier\Field\Archipelago;
use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Field;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Archipelago::class)]
#[CoversClass(AbstractField::class)]
#[CoversClass(Field::class)]
final class ArchipelagoTest extends TestCase
{
    public function testLevelsAboveTheAvailableReliefAreOmitted(): void
    {
        $shapes = Field::archipelago(32.0, 32.0, cell: 8.0, levels: 6)->shapes();

        self::assertNotEmpty($shapes);
        self::assertLessThan(6, count($shapes));
        foreach ($shapes as $shape) {
            self::assertNotSame('', $shape->getAttribute('d'));
        }
    }

    public function testTheFactoryReturnsTheAreaItWasGiven(): void
    {
        $archipelago = Field::archipelago(600.0, 400.0);

        self::assertSame(600.0, $archipelago->width());
        self::assertSame(400.0, $archipelago->height());
    }

    public function testOneShapePerLevel(): void
    {
        self::assertCount(3, Field::archipelago(600.0, 400.0, levels: 3, seed: 2)->shapes());
    }

    /**
     * A ring that does not close cannot be filled, so every subpath ends on the
     * point it opened with.
     */
    public function testEveryRingIsClosed(): void
    {
        foreach (Field::archipelago(600.0, 400.0, seed: 2)->shapes() as $level) {
            $data = (string) $level->getAttribute('d');

            self::assertSame('evenodd', $level->getAttribute('fill-rule'));

            foreach (array_filter(explode('M', $data)) as $ring) {
                self::assertStringEndsWith('Z', $ring);
            }
        }
    }

    /**
     * The window that brings the height field down to nothing along the border
     * is what closes the rings, and it also keeps the water around the islands.
     */
    public function testNoCoastReachesTheBorder(): void
    {
        foreach (Field::archipelago(600.0, 400.0, seed: 2)->shapes() as $level) {
            preg_match_all('/-?\d+(?:\.\d+)?/', (string) $level->getAttribute('d'), $matches);

            foreach (array_chunk(array_map(floatval(...), $matches[0]), 2) as [$x, $y]) {
                self::assertGreaterThan(0.0, $x);
                self::assertLessThan(600.0, $x);
                self::assertGreaterThan(0.0, $y);
                self::assertLessThan(400.0, $y);
            }
        }
    }

    public function testAHigherLevelIsDrawnDarkerThanTheOneBelowIt(): void
    {
        $shapes = Field::archipelago(600.0, 400.0, levels: 3, seed: 2)->shapes();

        self::assertLessThan(
            (float) $shapes[2]->getAttribute('fill-opacity'),
            (float) $shapes[0]->getAttribute('fill-opacity'),
        );
    }

    public function testTheSameSeedDrawsTheSameIslands(): void
    {
        self::assertSame(
            Field::archipelago(600.0, 400.0, seed: 3)->toSvg(),
            Field::archipelago(600.0, 400.0, seed: 3)->toSvg(),
        );
    }

    public function testACellCoarserThanTheGuardIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::archipelago(600.0, 400.0, cell: 200.0);
    }

    public function testMoreLevelsThanTheGuardAllowsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::archipelago(600.0, 400.0, levels: 7);
    }
}
