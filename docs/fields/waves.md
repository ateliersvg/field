---
title: Waves
description: Layered sine bands, each closed to the bottom of the frame, read as water seen from the shore.
order: 10
---

# Waves

Sample a sine across the width and shut it along the bottom edge: the curve becomes a mass.
Stack several of them, walking their baselines down the frame and deepening their tone as they
come forward, and the surface reads as water seen from the shore.

![Layered sine bands closed to the bottom of the frame](../images/waves.svg)

## Example

```php
<?php

declare(strict_types=1);

use Atelier\Field\Field;

require __DIR__.'/vendor/autoload.php';

echo Field::waves(width: 360, height: 240, layers: 5, seed: 1)->toSvg();
```

The figures use this 360 by 240 example and its explicit seed. Only the display colour
is changed to the documentation accent. Each variant changes only the setting in its caption.

## Options

| Setting | Default | Effect |
|---|---|---|
| `width` | none | area to cover, in user units |
| `height` | none | area to cover, in user units |
| `layers` | `5` | bands in the stack, 1 to 24; more bands means a shallower step between them |
| `seed` | `1` | amplitude, frequency and phase of every band |
| `withTheme(Theme)` | `Theme::default()` | colours, in one call |
| `withColor(string)` | `currentColor` | the ink the bands are painted with |
| `withBackground(?string)` | none | a ground behind the drawing |
| `withOpacity(?float)` | none | opacity of the whole drawing at once |

Amplitude is taken as a fraction of the height, so a band keeps its proportion when the frame
changes. Frequency is counted over the width, which is why a wide frame shows the same number of
crests as a narrow one and the swell stretches instead of repeating.

## Variants

One setting moved, the rest held: `layers` is the stack, `seed` is the water.

<div class="figure-grid figure-grid--notes">
<figure><img src="../images/waves-layers-few.svg" alt="Two sine bands, a deep step between them"><figcaption><code>layers: 2</code>. Two bands instead of five. The ramp runs from 0.18 to 0.80 whatever the count, so a short stack shows both of its ends at once.</figcaption></figure>
<figure><img src="../images/waves-layers-many.svg" alt="Twelve sine bands, each a shallow step below the one behind it"><figcaption><code>layers: 12</code>. Twelve bands over the same height. The step between two tones falls to about 0.06, and the stack reads as one graded mass.</figcaption></figure>
<figure><img src="../images/waves-seed-alt.svg" alt="Five sine bands carrying another swell"><figcaption><code>seed: 6</code>. The same five bands, another draw. The seed sets amplitude, frequency and phase, so the water changes and the stack stays.</figcaption></figure>
</div>

## Depth, by colour or by tone

A theme carrying a palette supplies band colours, read back to front and cycled when there
are more bands than entries. The foreground does not override these palette fills.

```php
use Atelier\Field\Theme;

Field::waves(360, 240)->withTheme(Theme::blueprint());
```

With no palette, the bands share one ink and are separated by opacity alone, from 0.18 at the
back to 0.80 at the front. That is what keeps a single colour readable as several planes.

```php
Field::waves(360, 240)->withColor('#0067a0');
```

The palette is a ramp rather than a set of series colours: colours picked to be told apart read
as stripes, colours that run in one direction read as distance.
