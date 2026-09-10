---
title: Dunes
description: Ridges of sand read from a noise field, smooth on the rise and long in the hollow.
order: 20
---

# Dunes

A dune has no period. Its crest is read from a noise field rather than from a sine, so no two
rises along one ridge are alike and the line still never breaks: value noise is continuous,
which a sequence of random heights is not.

![Ridges of sand, smooth on the rise and long in the hollow](../images/dunes.svg)

## Example

```php
<?php

declare(strict_types=1);

use Atelier\Field\Field;

require __DIR__.'/vendor/autoload.php';

echo Field::dunes(width: 360, height: 240, layers: 4, seed: 1)->toSvg();
```

The figures use this 360 by 240 example and its explicit seed. Only the display colour
is changed to the documentation accent. Each variant changes only the setting in its caption.

## Options

| Setting | Default | Effect |
|---|---|---|
| `width` | none | area to cover, in user units |
| `height` | none | area to cover, in user units |
| `layers` | `4` | ridges in the stack, 1 to 24 |
| `seed` | `1` | the shape of every crest |
| `withTheme(Theme)` | `Theme::default()` | colours, in one call |
| `withColor(string)` | `currentColor` | the ink the ridges are painted with |
| `withBackground(?string)` | none | a ground behind the drawing |
| `withOpacity(?float)` | none | opacity of the whole drawing at once |

Each layer reads a row of the noise field at a fixed row offset. Changing `layers` preserves
that row selection for a given index, but recalculates its depth, baseline, amplitude and
roughness. Earlier ridges therefore move and change shape when the total count changes.

## Variants

One setting moved, the rest held: `layers` is the stack, `seed` is the relief under it.

<div class="figure-grid figure-grid--notes">
<figure><img src="../images/dunes-layers-few.svg" alt="Two ridges of sand, a long hollow between them"><figcaption><code>layers: 2</code>. Two ridges instead of four, drawn at the two ends of the ramp, 0.20 at the back and 0.80 at the front.</figcaption></figure>
<figure><img src="../images/dunes-layers-many.svg" alt="Eight ridges of sand stacked toward the horizon"><figcaption><code>layers: 8</code>. Eight ridges. Baselines walk down in even steps, so a taller stack packs them and the field reads as depth.</figcaption></figure>
<figure><img src="../images/dunes-seed-alt.svg" alt="Four ridges of sand cut from another relief"><figcaption><code>seed: 5</code>. The same four layer indices read another noise field, changing the ridge profiles.</figcaption></figure>
</div>

## What makes it sand

Two choices separate a dune from a wave.

The crest is asymmetric: a rise is rounded and a hollow is long and shallow, because sand piles
into a rounded back and settles on an apron. A symmetric curve reads as water.

Amplitude is derived from the frame height and each ridge's depth. Baseline spacing also
depends on the total layer count, so changing that count changes how much neighbouring ridges
overlap. A crest can rise past the ridge behind it, giving the stack its depth.

Nearer ridges are read at a lower frequency, which is what distance does to a dune field: the
far ones crowd together.
