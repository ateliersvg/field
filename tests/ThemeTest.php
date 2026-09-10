<?php

declare(strict_types=1);

namespace Atelier\Field\Tests;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Theme;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Theme::class)]
final class ThemeTest extends TestCase
{
    public function testDefaultInheritsTheHostColorAndPaintsNoGround(): void
    {
        $theme = Theme::default();

        self::assertSame('currentColor', $theme->foregroundColor);
        self::assertNull($theme->backgroundColor);
        self::assertNull($theme->opacity);
        self::assertFalse($theme->hasPalette());
    }

    public function testColorAtFallsBackToTheForegroundWithoutAPalette(): void
    {
        self::assertSame('currentColor', Theme::default()->colorAt(3));
    }

    public function testColorAtWrapsRoundThePalette(): void
    {
        $theme = new Theme(palette: ['#a', '#b', '#c']);

        self::assertSame('#a', $theme->colorAt(0));
        self::assertSame('#c', $theme->colorAt(2));
        self::assertSame('#a', $theme->colorAt(3));
    }

    public function testColorAtAcceptsANegativeIndex(): void
    {
        self::assertSame('#c', (new Theme(palette: ['#a', '#b', '#c']))->colorAt(-1));
    }

    public function testWithKeepsEveryRoleItIsNotGiven(): void
    {
        $theme = Theme::mono()->with(foregroundColor: '#0067a0');

        self::assertSame('#0067a0', $theme->foregroundColor);
        self::assertSame(Theme::mono()->backgroundColor, $theme->backgroundColor);
        self::assertSame(Theme::mono()->palette, $theme->palette);
    }

    public function testEveryPresetCarriesAPalette(): void
    {
        foreach ([Theme::dark(), Theme::mono(), Theme::blueprint(), Theme::neutral()] as $theme) {
            self::assertTrue($theme->hasPalette());
            self::assertNotNull($theme->backgroundColor);
        }
    }

    public function testABlankColorIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Theme('  ');
    }

    public function testABlankPaletteColorIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Theme(palette: ['#a', '']);
    }

    public function testAnOpacityOutsideTheUnitRangeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Theme(opacity: 1.5);
    }
}
