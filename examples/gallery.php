<?php

declare(strict_types=1);

use Atelier\Field\Field;
use Atelier\Field\FieldInterface;
use Atelier\Field\Theme;

require __DIR__.'/../vendor/autoload.php';

/** @var list<array{string, string, FieldInterface}> $figures */
$figures = [
    ['waves', 'Layered sine bands closed to the bottom.', Field::waves(360.0, 240.0)],
    ['waves, 9 layers', 'A deeper stack over the same frame.', Field::waves(360.0, 240.0, layers: 9)],
    ['dunes', 'Crests read from a noise field.', Field::dunes(360.0, 240.0)],
    ['dunes, 6 ridges', 'The far ridges crowding together.', Field::dunes(360.0, 240.0, layers: 6)],
    ['mountains', 'A crest alternating with a saddle.', Field::mountains(360.0, 240.0, seed: 9)],
    ['mountains, 2 ranges', 'Fewer ranges, larger masses.', Field::mountains(360.0, 240.0, layers: 2)],
    ['skyline', 'Two ranks of blocks, right angles only.', Field::skyline(360.0, 240.0)],
    ['skyline, 3 ranks', 'A third rank, further back and paler.', Field::skyline(360.0, 240.0, layers: 3)],
    ['cloud', 'The upper envelope of a row of discs.', Field::cloud(360.0, 240.0)],
    ['cloud, 9 puffs', 'A longer row over the same span.', Field::cloud(360.0, 240.0, puffs: 9)],
    ['blob', 'One closed curve, no corner anywhere.', Field::blob(360.0, 240.0)],
    ['blob, 12 nodes', 'More nodes, more reach between them.', Field::blob(360.0, 240.0, nodes: 12, irregularity: 0.45)],

    ['contours', 'Isolines interpolated across each cell.', Field::contours(360.0, 240.0)],
    ['contours, 20 levels', 'The same relief, read more finely.', Field::contours(360.0, 240.0, levels: 20)],
    ['lowPoly', 'A jittered lattice, cut both ways.', Field::lowPoly(360.0, 240.0, cell: 45.0)],
    ['lowPoly, 12 tones', 'A longer ramp over the same cut.', Field::lowPoly(360.0, 240.0, cell: 45.0, tones: 12)],
    ['voronoi', 'Cells relaxed toward their own middles.', Field::voronoi(360.0, 240.0)],
    ['voronoi, unrelaxed', 'The same sites, left where they fell.', Field::voronoi(360.0, 240.0, relax: 0)],
    ['flowField', 'Lines walking a field of directions.', Field::flowField(360.0, 240.0)],
    ['flowField, long', 'Fewer starts, a longer walk.', Field::flowField(360.0, 240.0, lines: 120, length: 140)],

    ['halftone', 'Dot radius falling away from the light.', Field::halftone(360.0, 240.0)],
    ['halftone, centred', 'The light moved to the middle.', Field::halftone(360.0, 240.0, spacing: 10.0, focusX: 0.5, focusY: 0.5)],
    ['moire', 'Two rulings, four degrees apart.', Field::moire(360.0, 240.0)],
    ['moire, wider beat', 'A smaller angle spreads the bands.', Field::moire(360.0, 240.0, angle: 2.0)],
    ['waveInterference', 'Three sources, and the lenses between them.', Field::waveInterference(360.0, 240.0)],
    ['waveInterference, five', 'More sources, a denser crossing.', Field::waveInterference(360.0, 240.0, sources: 5, spacing: 10.0)],

    ['strata', 'Beds of rock, dipping across the frame.', Field::strata(360.0, 240.0, seed: 3)],
    ['strata, sixteen beds', 'The same section, cut finer.', Field::strata(360.0, 240.0, beds: 16, seed: 3)],
    ['terrain', 'A lattice in perspective over a relief.', Field::terrain(360.0, 240.0)],
    ['archipelago', 'Islands as the filled level sets of a height field.', Field::archipelago(360.0, 240.0, 6.0, 3, 11)],
    ['rays', 'Wedges from a point below the frame.', Field::rays(360.0, 240.0, 17)],
    ['rays, centred', 'The same fan read as a wheel.', Field::rays(360.0, 240.0, 17, focusY: 0.5)],
    ['foam', 'Discs packed until there is no room left.', Field::foam(360.0, 240.0, 18.0)],

    ['mountains, blueprint', 'A palette replaces the opacity ramp.', Field::mountains(360.0, 240.0)->withTheme(Theme::blueprint())],
    ['voronoi, neutral', 'A grey ramp on an off-white ground.', Field::voronoi(360.0, 240.0)->withTheme(Theme::neutral())],
    ['contours, one ink', 'A single colour, no palette needed.', Field::contours(360.0, 240.0)->withColor('#0067a0')],
];

$cards = '';

foreach ($figures as [$name, $note, $field]) {
    $cards .= sprintf(
        '<figure><div class="frame">%s</div><figcaption><b>%s</b><span>%s</span></figcaption></figure>',
        $field->toSvg(),
        htmlspecialchars($name, \ENT_QUOTES),
        htmlspecialchars($note, \ENT_QUOTES),
    );
}

$html = <<<HTML
<!doctype html>
<meta charset="utf-8">
<title>atelier/field</title>
<style>
  :root { color-scheme: light dark; --ink: #16181d; --ground: #faf9f6; --line: #e2e0d9; }
  @media (prefers-color-scheme: dark) { :root { --ink: #e7e9ee; --ground: #0d0f13; --line: #262a31; } }
  body { margin: 0; padding: 3rem clamp(1rem, 4vw, 4rem); background: var(--ground); color: var(--ink);
         font: 15px/1.5 system-ui, sans-serif; }
  h1 { font-size: 1.6rem; margin: 0 0 .4rem; }
  p.lede { margin: 0 0 3rem; max-width: 46ch; opacity: .7; }
  .grid { display: grid; gap: 2.5rem; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); }
  figure { margin: 0; }
  .frame { border: 1px solid var(--line); overflow: hidden; }
  .frame svg { display: block; width: 100%; height: auto; }
  figcaption { margin-top: .7rem; display: grid; gap: .15rem; }
  figcaption b { font-family: ui-monospace, monospace; font-weight: 600; }
  figcaption span { opacity: .65; }
</style>
<h1>atelier/field</h1>
<p class="lede">Eighteen drawings that cover the area they are given. A field has a horizon or a
centre, so no repetition of it can be seamless. Colour comes from the host through currentColor.</p>
<div class="grid">$cards</div>
HTML;

$target = __DIR__.'/output';

if (!is_dir($target)) {
    mkdir($target, 0o755, true);
}

file_put_contents($target.'/index.html', $html);

echo "Wrote {$target}/index.html\n";
