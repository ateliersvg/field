---
title: Strata
description: A section through sedimentary rock, beds of unequal thickness dipping across the frame.
order: 25
---

# Strata

A cliff face read as a section: beds of rock stacked one on another, each one a different
thickness, all of them tilted the same way.

Every boundary is the sum of the thicknesses above it. Summing rather than drawing each boundary
on its own is what keeps two of them from crossing however much the thicknesses vary, and letting
a thickness fall away along its length is what lets a bed pinch out the way a real one does.

![Sedimentary beds of unequal thickness, dipping across the frame](../images/strata.svg)

## Example

```php
<?php

declare(strict_types=1);

use Atelier\Field\Field;

require __DIR__.'/vendor/autoload.php';

echo Field::strata(width: 360, height: 240, beds: 8, dip: 5, seed: 1)->toSvg();
```

The figures use this 360 by 240 example and its explicit seed. Only the display colour
is changed to the documentation accent. Each variant changes only the setting in its caption.

## Options

| Setting | Default | Effect |
|---|---|---|
| `width` | none | area to cover, in user units |
| `height` | none | area to cover, in user units |
| `beds` | `8` | beds in the section, 2 to 24 |
| `dip` | `5` | tilt of the whole section, in degrees, at most 30 either way |
| `seed` | `1` | the thicknesses and the roughness of every boundary |
| `withTheme(Theme)` | `Theme::default()` | colours, in one call |
| `withColor(string)` | `currentColor` | the ink the beds are painted with |
| `withBackground(?string)` | none | a ground behind the drawing |
| `withOpacity(?float)` | none | opacity of the whole drawing at once |

Thicknesses are shares of the height rather than measurements, so a section keeps its proportions
when the frame changes. The dip is applied after them, which is why a steep one runs a bed off
the top of the frame instead of squeezing the stack.

## Variants

One setting moved, the rest held: `beds` is the section, `dip` is the tilt, `seed` is the rock.

<div class="figure-grid figure-grid--notes">
<figure><img src="../images/strata-beds-few.svg" alt="Four beds across the same section"><figcaption><code>beds: 4</code>. Four beds instead of eight. The ramp runs from 0.22 to 0.85 whatever the count, so a short stack shows both of its ends at once.</figcaption></figure>
<figure><img src="../images/strata-beds-many.svg" alt="Sixteen beds across the same section"><figcaption><code>beds: 16</code>. Sixteen beds over the same height. The tone step falls to about 0.04 and the section reads as a graded mass rather than as courses.</figcaption></figure>
<figure><img src="../images/strata-dip-steep.svg" alt="The same section tilted further"><figcaption><code>dip: 18</code>. The same beds tilted more than three times as far, which is what runs the top bed off the frame on one side.</figcaption></figure>
</div>

## Cut by the frame

The first boundary is drawn a height above the frame and the last a height below it, so the
section is cut by the frame rather than floating inside it. The beds between them are the only
ones the reader ever sees whole.

That is also what lets the dip work. A tilt moves every boundary up on one side and down on the
other, and without the two boundaries outside the frame the tilt would open a gap along the top
and the bottom edges.
