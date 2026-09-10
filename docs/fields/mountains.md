---
title: Mountains
description: Ranges of straight segments, a crest alternating with a saddle.
order: 30
---

# Mountains

A ridge line of straight segments, where a crest always follows a saddle. That alternation is
what makes a range: heights drawn one after another with no such rule read as a sawtooth.

![Ranges of straight segments, crests alternating with saddles](../images/mountains.svg)

## Example

```php
<?php

declare(strict_types=1);

use Atelier\Field\Field;

require __DIR__.'/vendor/autoload.php';

echo Field::mountains(width: 360, height: 240, layers: 4, seed: 1)->toSvg();
```

The figures use this 360 by 240 example and its explicit seed. Only the display colour
is changed to the documentation accent. Each variant changes only the setting in its caption.

## Options

| Setting | Default | Effect |
|---|---|---|
| `width` | none | area to cover, in user units |
| `height` | none | area to cover, in user units |
| `layers` | `4` | ranges in the stack, 1 to 24 |
| `seed` | `1` | the height of every crest and saddle |
| `withTheme(Theme)` | `Theme::default()` | colours, in one call |
| `withColor(string)` | `currentColor` | the ink the ranges are painted with |
| `withBackground(?string)` | none | a ground behind the drawing |
| `withOpacity(?float)` | none | opacity of the whole drawing at once |

Both edges of the frame are saddles, which is what keeps a peak from being cut in half by the
border.

Every height is a fraction of the frame, so a range keeps its proportion at any size.

## Variants

One setting moved, the rest held: `layers` is how much distance the range states, `seed` is the ridge itself.

<div class="figure-grid figure-grid--notes">
<figure><img src="../images/mountains-layers-one.svg" alt="A single range of straight segments"><figcaption><code>layers: 1</code>. One range, drawn at the front tone and standing at mid height. A lone silhouette has no distance to state, so it takes the strength of a near range.</figcaption></figure>
<figure><img src="../images/mountains-layers-many.svg" alt="Seven ranges, the ones behind kept pale"><figcaption><code>layers: 7</code>. Seven ranges. Peak count rises with distance, which is why the far ranges are the busy ones and the near range stays a few large masses.</figcaption></figure>
<figure><img src="../images/mountains-seed-alt.svg" alt="Four ranges carrying another set of crests"><figcaption><code>seed: 3</code>. The same four ranges with another set of crests and saddles.</figcaption></figure>
</div>

## Distance

Ranges further back carry more peaks and reach higher, the two things distance does to a skyline
of rock. The near range is left with a few large masses.

Nodes are spaced evenly and then shifted inside their own half step. Their order never changes,
so the segments cannot cross, and the ridge loses the metronome an even spacing gives it.
