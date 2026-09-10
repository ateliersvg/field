<?php

declare(strict_types=1);

namespace Atelier\Field\Tests;

use Atelier\Field\AbstractField;
use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Field;
use Atelier\Field\Theme;
use Atelier\Field\Waves;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Waves::class)]
#[CoversClass(AbstractField::class)]
#[CoversClass(Field::class)]
final class WavesTest extends TestCase
{
    public function testTheFactoryReturnsTheAreaItWasGiven(): void
    {
        $waves = Field::waves(600.0, 400.0);

        self::assertSame(600.0, $waves->width());
        self::assertSame(400.0, $waves->height());
    }

    public function testOneShapePerLayer(): void
    {
        self::assertCount(7, Field::waves(600.0, 400.0, layers: 7)->shapes());
    }

    public function testASingleLayerIsDrawn(): void
    {
        self::assertCount(1, Field::waves(600.0, 400.0, layers: 1)->shapes());
    }

    public function testASingleLayerTakesTheFrontTone(): void
    {
        $shapes = Field::waves(600.0, 400.0, layers: 1)->shapes();

        self::assertSame('0.8', $shapes[0]->getAttribute('fill-opacity'));
    }

    public function testTheSameSeedDrawsTheSameField(): void
    {
        self::assertSame(
            Field::waves(600.0, 400.0, seed: 7)->toSvg(),
            Field::waves(600.0, 400.0, seed: 7)->toSvg(),
        );
    }

    public function testTwoSeedsDrawTwoFields(): void
    {
        self::assertNotSame(
            Field::waves(600.0, 400.0, seed: 1)->toSvg(),
            Field::waves(600.0, 400.0, seed: 2)->toSvg(),
        );
    }

    public function testEveryBandIsClosedOnTheBottomEdge(): void
    {
        foreach (Field::waves(600.0, 400.0)->shapes() as $band) {
            self::assertStringEndsWith('L600 400L0 400Z', (string) $band->getAttribute('d'));
        }
    }

    public function testLayersAreSeparatedByOpacityWithoutAPalette(): void
    {
        $shapes = Field::waves(600.0, 400.0, layers: 3)->shapes();

        foreach ($shapes as $band) {
            self::assertSame('currentColor', $band->getAttribute('fill'));
            self::assertNotNull($band->getAttribute('fill-opacity'));
        }
    }

    public function testAPaletteReplacesTheOpacityRamp(): void
    {
        $shapes = Field::waves(600.0, 400.0, layers: 2)->withTheme(new Theme(palette: ['#a', '#b']))->shapes();

        self::assertSame('#a', $shapes[0]->getAttribute('fill'));
        self::assertSame('#b', $shapes[1]->getAttribute('fill'));
        self::assertNull($shapes[0]->getAttribute('fill-opacity'));
    }

    public function testABackgroundPaintsAGroundBehindTheDrawing(): void
    {
        $svg = Field::waves(600.0, 400.0)->withBackground('#F6F5F1')->toSvg();

        self::assertStringContainsString('#F6F5F1', $svg);
    }

    public function testTheDocumentTakesTheSizeOfTheField(): void
    {
        $svg = Field::waves(320.0, 240.0)->toSvg();

        self::assertStringContainsString('320', $svg);
        self::assertStringContainsString('240', $svg);
    }

    public function testTheDataUriCarriesTheDrawing(): void
    {
        self::assertStringStartsWith('data:image/svg+xml,', Field::waves(600.0, 400.0)->toDataUri());
    }

    public function testTheCssDeclaresABackgroundImage(): void
    {
        self::assertStringContainsString('background-image: url("data:image/svg+xml,', Field::waves(600.0, 400.0)->toCss());
    }

    public function testStyleReturnsANewFieldAndLeavesTheOriginalAlone(): void
    {
        $waves = Field::waves(600.0, 400.0);
        $colored = $waves->withColor('#0067a0');

        self::assertNotSame($waves, $colored);
        self::assertSame('currentColor', $waves->theme()->foregroundColor);
        self::assertSame('#0067a0', $colored->theme()->foregroundColor);
    }

    public function testAnAreaWithNoSurfaceIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::waves(0.0, 400.0);
    }

    public function testAStackWithNoLayerIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::waves(600.0, 400.0, layers: 0);
    }

    public function testAStackDeeperThanTheGuardIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::waves(600.0, 400.0, layers: 25);
    }
}
