---
title: Getting started
description: Install Atelier Field, cover an area with a procedural drawing, and colour it after the fact.
order: 10
---

# Getting started

## Install

```bash
composer require atelier/field
```

Requires PHP 8.3 or later and `atelier/svg`. Randomness comes from `ext-random`, bundled with PHP
since 8.2, so nothing else has to be installed for a seeded field to draw the same thing twice.

## Cover an area

A factory takes the area first, then its own geometry, then a seed. What comes back is a value:
it needs no document to exist, and building one mutates nothing.

```php
<?php

declare(strict_types=1);

use Atelier\Field\Field;

require __DIR__.'/vendor/autoload.php';

echo Field::waves(1200, 400, layers: 6, seed: 7)->toSvg();
```

`toSvg()` returns a standalone document at the size the field was given. It knows that size, so
unlike a tile it needs no dimensions passed back to it.

## Put it in a document you already hold

```php
use Atelier\Svg\Document;

$document = Document::create(1200, 400);

$document->getRootElement()?->appendChild(
    Field::waves(1200, 400, layers: 6, seed: 7)->element()
);
```

`element()` returns one `<g>` holding the whole drawing, ground included when the theme paints
one. Nothing is registered in `<defs>`: a field is content, not a paint server.

## Take it as a CSS background

```php
echo Field::waves(1600, 500)->withColor('#0067a0')->toCss();
```

```css
background-image: url("data:image/svg+xml,...");
background-repeat: no-repeat;
background-size: cover;
```

A background image is a document of its own, so `currentColor` resolves to black there. Set a
real colour before calling `toDataUri()` or `toCss()`.

## Colour it

```php
use Atelier\Field\Theme;

Field::waves(1200, 400)
    ->withTheme(Theme::blueprint()->with(palette: []))
    ->withColor('#0067a0')
    ->withBackground('#f6f5f1')
    ->withOpacity(0.6);
```

Each call returns a new field. The default foreground is `currentColor` and no ground is
painted, so inline SVG inherits the colour of its ancestors.

A nonempty palette takes precedence over the foreground for toned fills. `withColor()` changes
only the foreground; it does not replace palette entries. The example clears the palette to
use one ink. With a palette, colours cycle when there are more layers than entries.
Without a palette, the layers are told
apart by depth alone. The palette is a ramp read back to front, not a set of series colours: a
categorical palette on a stack of layers reads as stripes rather than as distance.

## The same seed draws the same field

```php
Field::waves(1200, 400, seed: 7)->toSvg() === Field::waves(1200, 400, seed: 7)->toSvg();
```

Randomness is `Random\Randomizer` over `Random\Engine\Xoshiro256StarStar`, both from the PHP
core. Identical inputs reproduce the same drawing with the same generator implementation
and supported runtime behavior. Fixed output fixtures detect changes during development; they
do not promise byte stability across package or dependency releases. Keep an exported SVG when
you need a permanent asset.

## Input limits

Width and height must be finite and greater than zero. Each generator also validates its own
counts and proportions; see its options table for the exact interval. Some controls accept
zero, including opacity, Terrain relief and Voronoi stroke thickness. `NAN` and `INF` are rejected.

Each invalid call below is caught separately, so both examples execute:

```php
foreach ([['width' => 0, 'height' => 400], ['width' => 1200, 'height' => 400, 'layers' => 0]] as $arguments) {
    try {
        Field::waves(...$arguments);
    } catch (\Atelier\Field\Exception\InvalidArgumentException $error) {
        echo $error->getMessage(), PHP_EOL;
    }
}
```

## Viewport, coverage and resizing

Width and height define the drawing's coordinate frame, not a promise to paint every pixel.
Blob, Halftone, Foam and Archipelago deliberately leave transparent regions. `withBackground()`
paints the whole frame. Geometry clipping limits path coordinates; a stroke can extend half
its width beyond its centreline. An appended group does not clip its own stroke bounds.

Standalone exports include a matching `viewBox`. Resizing that SVG scales its existing geometry,
including cell sizes and strokes. Regenerating with different dimensions recalculates the layout
and may change sample counts and shape positions. For an appended group, set the enclosing SVG's
`viewBox` or apply a transform yourself.

## Precision and cost

Coordinates serialize to four decimal places. Work in ordinary display-sized user units, such
as a frame hundreds or thousands of units wide, and scale the SVG when a different display size
is needed. Features below `0.00005` units can round to zero. Very large values can overflow
intermediate calculations even when the inputs are finite; guards do not establish useful output
at every accepted scale.

Cost depends on the generator. Fixed layer or ray counts differ from lattices whose sample count
grows with width and height divided by cell size. Avoid extreme area-to-cell ratios; the package
does not impose a common time, memory or output-size bound.
