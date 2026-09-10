---
title: Cloud
description: The shape a cloud is drawn as, a flat underside and a row of puffs above it.
order: 130
---

# Cloud

The outline is the upper envelope of a row of overlapping discs, sampled across the width. Where
two discs overlap the taller one wins, so the row merges into one mass.

![A flat underside and a row of puffs above it](../images/cloud.svg)

## Example

```php
<?php

declare(strict_types=1);

use Atelier\Field\Field;

require __DIR__.'/vendor/autoload.php';

echo Field::cloud(width: 360, height: 240, puffs: 5, seed: 1)->toSvg();
```

The figures use this 360 by 240 example and its explicit seed. Only the display colour
is changed to the documentation accent. Each variant changes only the setting in its caption.

## Options

| Setting | Default | Effect |
|---|---|---|
| `width` | none | area to cover, in user units |
| `height` | none | area to cover, in user units |
| `puffs` | `5` | discs the outline is built from, 2 to 16 |
| `seed` | `1` | the size of every puff |

Puff centres are spaced evenly and their radii drawn, so the row keeps its span and still reads
as unequal. The end puffs are the low ones, so the mass tapers instead of rising straight off
the ground at both edges, which would read as a wall.

## Variants

One setting moved, the rest held: `puffs` is the row, `seed` is the height of each one.

<div class="figure-grid figure-grid--notes">
<figure><img src="../images/cloud-puffs-few.svg" alt="A cloud built from three puffs"><figcaption><code>puffs: 3</code>. Three puffs. A puff is one of a row laid across the width, so a lower count makes each one wider and the mass taller.</figcaption></figure>
<figure><img src="../images/cloud-puffs-many.svg" alt="A cloud built from ten puffs"><figcaption><code>puffs: 10</code>. Ten puffs, by the same rule the other way: the row is finer, each disc smaller, and the cloud sits lower.</figcaption></figure>
<figure><img src="../images/cloud-seed-alt.svg" alt="A cloud of five puffs at other heights"><figcaption><code>seed: 4</code>. The same five puffs drawn at other heights. Position across the width is ruled, and only the rise is drawn.</figcaption></figure>
</div>

## Why an envelope

Drawing the discs themselves would be quicker, and it would show every seam between them the
moment the fill is anything but opaque, which is exactly what a theme with an opacity does.

Chaining arcs along the top leaves a scallop at every join, and a scalloped top reads as a wave.
The envelope has neither problem: it is one path, one fill, and the only line it draws is the
outside of the union.

Radii overlap their neighbours by construction, so the row can never come apart. A test walks
the middle of the outline and checks it never returns to the base.
