---
title: Skyline
description: A city in silhouette, blocks of unequal width and height drawn with right angles alone.
order: 40
---

# Skyline

A city seen from across the water. The profile is drawn block by block, each one a rise, a roof
and a fall, so the silhouette is made of right angles alone.

![A city in silhouette, blocks of unequal width and height](../images/skyline.svg)

## Example

```php
<?php

declare(strict_types=1);

use Atelier\Field\Field;

require __DIR__.'/vendor/autoload.php';

echo Field::skyline(width: 360, height: 240, layers: 2, seed: 1)->toSvg();
```

The figures use this 360 by 240 example and its explicit seed. Only the display colour
is changed to the documentation accent. Each variant changes only the setting in its caption.

## Options

| Setting | Default | Effect |
|---|---|---|
| `width` | none | area to cover, in user units |
| `height` | none | area to cover, in user units |
| `layers` | `2` | ranks of buildings, 1 to 8 |
| `seed` | `1` | the width and height of every block |
| `withTheme(Theme)` | `Theme::default()` | colours, in one call |
| `withColor(string)` | `currentColor` | the ink the ranks are painted with |
| `withBackground(?string)` | none | a ground behind the drawing |
| `withOpacity(?float)` | none | opacity of the whole drawing at once |

Widths and heights are read as fractions of the frame, so the city keeps its proportion at any
size.

Two ranks are drawn by default. The back one stands paler and lower, which is the only depth cue
a flat silhouette can carry.

## Variants

One setting moved, the rest held: `layers` is how many rows stand behind one another, `seed` is the plan of the blocks.

<div class="figure-grid figure-grid--notes">
<figure><img src="../images/skyline-layers-one.svg" alt="One row of blocks standing on the bottom edge"><figcaption><code>layers: 1</code>. One row, drawn at the front tone. Nothing stands behind it, so nothing is faded.</figcaption></figure>
<figure><img src="../images/skyline-layers-many.svg" alt="Four rows of blocks, the ones behind kept pale"><figcaption><code>layers: 4</code>. Four rows. Each row forward stands on a lower ground line and reaches higher, which is what puts one row in front of another.</figcaption></figure>
<figure><img src="../images/skyline-seed-alt.svg" alt="Two rows of blocks cut to other widths"><figcaption><code>seed: 7</code>. The same two rows cut into blocks of other widths and heights.</figcaption></figure>
</div>

## The last block

A block landing just short of the right edge would leave a sliver one unit wide, and a sliver
reads as a scratch rather than as a building. When the remainder is shorter than six tenths of a
block, the last block takes it and runs to the edge.
