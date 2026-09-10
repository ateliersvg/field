<?php

declare(strict_types=1);

namespace Atelier\Field\Tests;

use Atelier\Field\Blob;
use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Field;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Blob::class)]
final class BlobTest extends TestCase
{
    private static function outline(int $nodes = 7, int $seed = 1): string
    {
        return (string) Field::blob(400.0, 300.0, nodes: $nodes, seed: $seed)->shapes()[0]->getAttribute('d');
    }

    public function testTheFactoryReturnsTheAreaItWasGiven(): void
    {
        $blob = Field::blob(400.0, 300.0);

        self::assertSame(400.0, $blob->width());
        self::assertSame(300.0, $blob->height());
    }

    public function testOneClosedPath(): void
    {
        self::assertCount(1, Field::blob(400.0, 300.0)->shapes());
        self::assertStringEndsWith('Z', self::outline());
    }

    public function testOneCurveSegmentPerNode(): void
    {
        // Every node is joined to the next by a cubic, the last one back to the
        // first, so the seam closes with the same tangent as any other node.
        self::assertSame(9, substr_count(self::outline(9), 'C'));
    }

    public function testItIsDrawnWithNoStraightEdge(): void
    {
        self::assertStringNotContainsString('L', self::outline());
    }

    public function testEveryNodeStaysInsideTheFrame(): void
    {
        preg_match_all('/(-?[\d.]+) (-?[\d.]+)/', self::outline(), $matches, \PREG_SET_ORDER);

        foreach ($matches as $point) {
            self::assertGreaterThanOrEqual(-0.001, (float) $point[1]);
            self::assertLessThanOrEqual(400.001, (float) $point[1]);
            self::assertGreaterThanOrEqual(-0.001, (float) $point[2]);
            self::assertLessThanOrEqual(300.001, (float) $point[2]);
        }
    }

    public function testTheSameSeedDrawsTheSameShape(): void
    {
        self::assertSame(self::outline(seed: 7), self::outline(seed: 7));
    }

    public function testTwoSeedsDrawTwoShapes(): void
    {
        self::assertNotSame(self::outline(seed: 1), self::outline(seed: 2));
    }

    public function testFewerThanThreeNodesIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::blob(400.0, 300.0, nodes: 2);
    }

    public function testAnIrregularityPastOneIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::blob(400.0, 300.0, irregularity: 1.4);
    }

    public function testAnAreaWithNoSurfaceIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::blob(0.0, 300.0);
    }
}
