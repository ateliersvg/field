<?php

declare(strict_types=1);

namespace Atelier\Field\Tests;

use Atelier\Field\Field;
use Atelier\Field\FieldInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Field::class)]
final class FieldTest extends TestCase
{
    /** @return iterable<string, array{string, class-string<FieldInterface>}> */
    public static function factories(): iterable
    {
        yield 'waves' => ['waves', \Atelier\Field\Waves::class];
        yield 'dunes' => ['dunes', \Atelier\Field\Dunes::class];
        yield 'mountains' => ['mountains', \Atelier\Field\Mountains::class];
        yield 'skyline' => ['skyline', \Atelier\Field\Skyline::class];
        yield 'cloud' => ['cloud', \Atelier\Field\Cloud::class];
        yield 'blob' => ['blob', \Atelier\Field\Blob::class];
        yield 'contours' => ['contours', \Atelier\Field\Contours::class];
        yield 'lowPoly' => ['lowPoly', \Atelier\Field\LowPoly::class];
        yield 'voronoi' => ['voronoi', \Atelier\Field\Voronoi::class];
        yield 'flowField' => ['flowField', \Atelier\Field\FlowField::class];
        yield 'halftone' => ['halftone', \Atelier\Field\Halftone::class];
        yield 'moire' => ['moire', \Atelier\Field\Moire::class];
        yield 'waveInterference' => ['waveInterference', \Atelier\Field\WaveInterference::class];
        yield 'strata' => ['strata', \Atelier\Field\Strata::class];
        yield 'terrain' => ['terrain', \Atelier\Field\Terrain::class];
        yield 'archipelago' => ['archipelago', \Atelier\Field\Archipelago::class];
        yield 'rays' => ['rays', \Atelier\Field\Rays::class];
        yield 'foam' => ['foam', \Atelier\Field\Foam::class];
    }

    /** @param class-string<FieldInterface> $expected */
    #[DataProvider('factories')]
    public function testFactoriesPreserveTheRequestedArea(string $factory, string $expected): void
    {
        $field = Field::$factory(801.0, 403.0);
        self::assertInstanceOf($expected, $field);
        self::assertSame(801.0, $field->width());
        self::assertSame(403.0, $field->height());
    }

    /** @return iterable<string, array{string, string}> */
    public static function seededOutputs(): iterable
    {
        yield 'waves' => ['waves', 'eddfd44d7217eb665fa90931a06e645d82cdc0da1ec98f5662d90123d70790b0'];
        yield 'dunes' => ['dunes', 'ee36d9ea7f24e663ce32a97ab16a6e171c7bb8658a43be6e881790a4a8130b22'];
        yield 'mountains' => ['mountains', '4ef42093bd9cbc1afc9ee10789d6314b3405d05b2b058319a2a6cbef97635d46'];
        yield 'skyline' => ['skyline', 'be8b08206013fac10518a4337c9fa619a41c14a4eaf2a8ea4487b6401a6a328c'];
        yield 'contours' => ['contours', 'c8a4a690ff4fe98fbf337694ddaa5f96a7a603ec2de85e5377f9c754f01a51ea'];
        yield 'lowPoly' => ['lowPoly', '194a8fe0c51c306cadba0259d249cf89a132e61d8dd9755872a9ecd47597222d'];
        yield 'voronoi' => ['voronoi', 'a1b372f41adff12c0caa0cf894f141dc62f0d781cbe36100a8f44e967f1d9813'];
        yield 'flowField' => ['flowField', '7d3ae896c8f657bdf160ee67a817207a52c54e7dd38e84bedf2af82d7312d331'];
        yield 'waveInterference' => ['waveInterference', 'adaad7cb64c1c012fbf1acfda6479a930b4d6780380bf313f676f5f830cb33ce'];
        yield 'blob' => ['blob', '7b8130ee2343182e9bfcd472839377a579b0e7c40bec2f5d0ad721ad42535a48'];
        yield 'cloud' => ['cloud', '0e6e45990b728bcb1bffb6358988f05bbd8ee7f99b0e878a43452099b350b3b8'];
        yield 'strata' => ['strata', 'a7ff60a7183c72ddd7f8f2a052cb8a343178f62df42819d441d9b05b860a269f'];
        yield 'terrain' => ['terrain', '377270eb6f0361317ae289f8945a43b4a1f321e514a1a4024bd10a935ea6955e'];
        yield 'archipelago' => ['archipelago', '0e1d177dc8a3f5b7f17fb783edc736963ad531dc21afab017b5732b50dc9e1fc'];
        yield 'foam' => ['foam', '26417a89e7c2ce45fdb65dd7095261ff418dd9cb88887f2da01bb136ddf7337b'];
    }

    #[DataProvider('seededOutputs')]
    public function testSeededSvgMatchesTheFixedFixture(string $factory, string $sha256): void
    {
        // Reviewed at 800 x 400, seed 7, with the default style. Do not regenerate
        // these expectations without reviewing the resulting visual changes.
        self::assertSame($sha256, hash('sha256', Field::$factory(800, 400, seed: 7)->toSvg()));
    }
}
