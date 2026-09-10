<?php

declare(strict_types=1);

namespace Atelier\Field\Tests;

use Atelier\Field\AbstractField;
use Atelier\Field\Field;
use Atelier\Field\Theme;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractField::class)]
final class AbstractFieldTest extends TestCase
{
    public function testOpacityAppliesToTheWholeGroupAndCanBeClearedImmutably(): void
    {
        $original = Field::waves(800, 400)->withTheme(new Theme('#123456', '#ffffff', ['#aaaaaa', '#bbbbbb']));
        $faded = $original->withOpacity(0.4);
        self::assertNull($original->element()->getAttribute('opacity'));
        self::assertSame('0.4', $faded->element()->getAttribute('opacity'));
        self::assertSame($original->theme()->palette, $faded->theme()->palette);
        self::assertSame($original->theme()->backgroundColor, $faded->theme()->backgroundColor);
        self::assertNull($faded->withOpacity(null)->element()->getAttribute('opacity'));
    }

    public function testBackgroundCanBeClearedWithoutChangingTheOriginal(): void
    {
        $original = Field::waves(800, 400)->withBackground('#fff');
        self::assertSame('rect', $original->element()->getChildren()[0]->getTagName());
        self::assertSame('path', $original->withBackground(null)->element()->getChildren()[0]->getTagName());
    }

    public function testExportsContainTheSameDrawing(): void
    {
        $field = Field::waves(800, 400)->withColor('#123456');
        self::assertSame($field->toSvg(), rawurldecode(substr($field->toDataUri(), strlen('data:image/svg+xml,'))));
        self::assertStringContainsString($field->toDataUri(), $field->toCss());
        $xml = new \DOMDocument();
        self::assertTrue($xml->loadXML($field->toSvg()));
        self::assertSame('800', $xml->documentElement->getAttribute('width'));
        self::assertSame('400', $xml->documentElement->getAttribute('height'));
        self::assertSame('0 0 800 400', $xml->documentElement->getAttribute('viewBox'));
        self::assertGreaterThan(0, $xml->getElementsByTagName('path')->length);
    }

    public function testForegroundDoesNotOverrideAPaletteAndClearingItRestoresOneInk(): void
    {
        $field = Field::waves(800, 400)->withTheme(Theme::blueprint());
        self::assertSame($field->toSvg(), $field->withColor('#123456')->toSvg());
        $singleInk = $field->withTheme($field->theme()->with(palette: []))->withColor('#123456');
        self::assertStringContainsString('fill="#123456"', $singleInk->toSvg());
        self::assertNotSame($field->toSvg(), $singleInk->toSvg());
    }
}
