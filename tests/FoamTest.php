<?php

declare(strict_types=1);

namespace Atelier\Field\Tests;

use Atelier\Field\AbstractField;
use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Field;
use Atelier\Field\Foam;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Foam::class)]
#[CoversClass(AbstractField::class)]
#[CoversClass(Field::class)]
final class FoamTest extends TestCase
{
    public function testTheFactoryReturnsTheAreaItWasGiven(): void
    {
        $foam = Field::foam(600.0, 400.0);

        self::assertSame(600.0, $foam->width());
        self::assertSame(400.0, $foam->height());
    }

    public function testTheFrameIsPackedWithDiscs(): void
    {
        self::assertGreaterThan(50, \count(Field::foam(600.0, 400.0)->shapes()));
    }

    /**
     * A disc is grown until it meets its nearest neighbour, so no two of them
     * ever overlap.
     */
    public function testNoTwoDiscsOverlap(): void
    {
        $discs = $this->discsOf(Field::foam(400.0, 300.0, radius: 20.0));
        $count = \count($discs);

        for ($first = 0; $first < $count; ++$first) {
            for ($second = $first + 1; $second < $count; ++$second) {
                [$x1, $y1, $r1] = $discs[$first];
                [$x2, $y2, $r2] = $discs[$second];

                self::assertGreaterThanOrEqual(
                    $r1 + $r2 - 0.0001,
                    hypot($x2 - $x1, $y2 - $y1),
                );
            }
        }
    }

    public function testEveryDiscStaysInsideTheFrame(): void
    {
        foreach ($this->discsOf(Field::foam(400.0, 300.0)) as [$x, $y, $radius]) {
            self::assertGreaterThanOrEqual(-0.0001, $x - $radius);
            self::assertGreaterThanOrEqual(-0.0001, $y - $radius);
            self::assertLessThanOrEqual(400.0001, $x + $radius);
            self::assertLessThanOrEqual(300.0001, $y + $radius);
        }
    }

    /**
     * The gradient is what makes this a field rather than a scatter: the discs
     * near the focus are the large ones.
     */
    public function testTheDiscsAreLargestAtTheFocus(): void
    {
        $near = [];
        $far = [];

        foreach ($this->discsOf(Field::foam(600.0, 400.0, focusX: 0.2, focusY: 0.2)) as [$x, $y, $radius]) {
            hypot($x - 120.0, $y - 80.0) < 120.0 ? $near[] = $radius : $far[] = $radius;
        }

        self::assertGreaterThan(
            array_sum($far) / \count($far),
            array_sum($near) / \count($near),
        );
    }

    public function testTheSameSeedDrawsTheSamePacking(): void
    {
        self::assertSame(
            Field::foam(600.0, 400.0, seed: 6)->toSvg(),
            Field::foam(600.0, 400.0, seed: 6)->toSvg(),
        );
    }

    public function testADiscLargerThanTheGuardIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::foam(600.0, 400.0, radius: 120.0);
    }

    public function testAFocusOutsideTheFrameIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::foam(600.0, 400.0, focusX: 1.2);
    }

    /**
     * @return list<array{float, float, float}>
     */
    private function discsOf(Foam $foam): array
    {
        $discs = [];

        foreach ($foam->shapes() as $disc) {
            $discs[] = [
                (float) $disc->getAttribute('cx'),
                (float) $disc->getAttribute('cy'),
                (float) $disc->getAttribute('r'),
            ];
        }

        return $discs;
    }
}
