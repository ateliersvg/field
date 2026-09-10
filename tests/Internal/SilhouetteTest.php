<?php

declare(strict_types=1);

namespace Atelier\Field\Tests\Internal;

use Atelier\Field\Internal\Silhouette;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Silhouette::class)]
final class SilhouetteTest extends TestCase
{
    public function testBandClosesTheProfileAlongTheBottomEdge(): void
    {
        self::assertSame('M0 4L5 2L10 3L10 8L0 8Z', Silhouette::band([[0.0, 4.0], [5.0, 2.0], [10.0, 3.0]], 10.0, 8.0)->getAttribute('d'));
    }

    public function testSamplesIncludeBothEdgesEvenWhenTheStepDoesNotDivideTheWidth(): void
    {
        self::assertSame([0.0, 2.5, 5.0, 7.5, 10.0], Silhouette::samples(10.0, 3.0));
        self::assertSame([0.0, 5.0, 10.0], Silhouette::samples(10.0, 100.0));
    }

    public function testDepthAndToneDistinguishSingleAndStackedLayers(): void
    {
        self::assertSame(0.5, Silhouette::depth(0, 1));
        self::assertSame(0.0, Silhouette::depth(0, 3));
        self::assertSame(1.0, Silhouette::depth(2, 3));
        self::assertSame(0.8, Silhouette::tone(0, 1, 0.2, 0.8));
        self::assertSame(0.2, Silhouette::tone(0, 3, 0.2, 0.8));
        self::assertSame(0.5, Silhouette::tone(1, 3, 0.2, 0.8));
    }
}
