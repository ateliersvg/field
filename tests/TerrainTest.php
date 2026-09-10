<?php

declare(strict_types=1);

namespace Atelier\Field\Tests;

use Atelier\Field\AbstractField;
use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Field;
use Atelier\Field\Terrain;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Terrain::class)]
#[CoversClass(AbstractField::class)]
#[CoversClass(Field::class)]
final class TerrainTest extends TestCase
{
    public function testTheFactoryReturnsTheAreaItWasGiven(): void
    {
        $terrain = Field::terrain(600.0, 400.0);

        self::assertSame(600.0, $terrain->width());
        self::assertSame(400.0, $terrain->height());
    }

    public function testTheLatticeIsDrawnBothWays(): void
    {
        $across = 0;
        $into = 0;

        foreach (Field::terrain(600.0, 400.0, rows: 8, columns: 10)->shapes() as $line) {
            $points = $this->pointsOf($line);
            $span = [
                abs($points[\count($points) - 1][0] - $points[0][0]),
                abs($points[\count($points) - 1][1] - $points[0][1]),
            ];

            $span[0] > $span[1] ? ++$across : ++$into;
        }

        // The outermost columns pass outside the frame along their whole
        // length and are dropped, so the count is not the lattice count.
        self::assertGreaterThanOrEqual(8, $across, 'Every row crosses the frame.');
        self::assertGreaterThan(0, $into, 'The columns running into the distance are drawn too.');
    }

    public function testEveryLineStaysInsideTheFrame(): void
    {
        foreach (Field::terrain(600.0, 400.0)->shapes() as $line) {
            foreach ($this->pointsOf($line) as [$x, $y]) {
                self::assertGreaterThanOrEqual(-0.01, $x);
                self::assertLessThanOrEqual(600.01, $x);
                self::assertGreaterThanOrEqual(-0.01, $y);
                self::assertLessThanOrEqual(400.01, $y);
            }
        }
    }

    /**
     * Distance is what the field states, and it states it twice: a far row is
     * drawn thinner and paler than a near one.
     */
    public function testANearRowIsDrawnHeavierThanAFarOne(): void
    {
        $shapes = Field::terrain(600.0, 400.0, rows: 12, columns: 12)->shapes();

        $near = $shapes[0];
        $far = $shapes[11];

        self::assertGreaterThan(
            (float) $far->getAttribute('stroke-width'),
            (float) $near->getAttribute('stroke-width'),
        );
        self::assertGreaterThan(
            (float) $far->getAttribute('stroke-opacity'),
            (float) $near->getAttribute('stroke-opacity'),
        );
    }

    public function testTheHorizonMovesTheWholeSurfaceDownTheFrame(): void
    {
        $high = $this->topOf(Field::terrain(600.0, 400.0, horizon: 0.2));
        $low = $this->topOf(Field::terrain(600.0, 400.0, horizon: 0.6));

        self::assertGreaterThan($high, $low);
    }

    public function testTheSameSeedDrawsTheSameRelief(): void
    {
        self::assertSame(
            Field::terrain(600.0, 400.0, seed: 5)->toSvg(),
            Field::terrain(600.0, 400.0, seed: 5)->toSvg(),
        );
    }

    public function testALatticeWithOneRowIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::terrain(600.0, 400.0, rows: 1);
    }

    public function testAHorizonOutsideTheFrameIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::terrain(600.0, 400.0, horizon: 1.4);
    }

    private function topOf(Terrain $terrain): float
    {
        $top = \PHP_FLOAT_MAX;

        foreach ($terrain->shapes() as $line) {
            foreach ($this->pointsOf($line) as [, $y]) {
                $top = min($top, $y);
            }
        }

        return $top;
    }

    /**
     * @return list<array{float, float}>
     */
    private function pointsOf(object $line): array
    {
        preg_match_all('/-?\d+(?:\.\d+)?/', (string) $line->getAttribute('d'), $matches);

        $points = [];

        foreach (array_chunk(array_map(floatval(...), $matches[0]), 2) as $pair) {
            $points[] = [$pair[0], $pair[1]];
        }

        return $points;
    }
}
