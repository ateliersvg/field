---
title: Atelier Field
description: Eighteen procedural SVG drawings for specified dimensions, with styles and parameter variants.
order: 0
---

# atelier/field

`atelier/field` produces drawings for the area they are given. It repeats nothing and
renders no image.

```
Field::waves(1200, 400, layers: 6, seed: 7)   an area, geometry, a seed
   -> withTheme() withColor() withBackground()    colour, immutably
   -> element()                                   a <g> to append
   -> toSvg() toDataUri() toCss()                 a document, a URI, a background
```

## The rule this package exists for

> A field is generated for a specified width and height and does not repeat.

The frame is a viewport, not a coverage guarantee: silhouettes leave sky, dots leave gaps,
and islands leave sea. A background colour can paint the complete frame.

It is the mirror of the rule [atelier/pattern](https://github.com/ateliersvg/pattern) is built
on. A tiling makes every point of the surface equivalent: no top, no bottom, no centre, no light
source. So anything carrying a horizon or a gradient of density cannot tile, by construction and
not for want of effort.

Mountains have sky above and ground below. A radial halftone has a centre where the dots are
fat. Isolines follow a relief that exists only here. Those are fields.

The distinction has a practical consequence. A tile weighs the same on any surface and is shared
between elements through `url(#id)`; a field lives inline and its cost depends on the generator
and sampling settings, as well as its dimensions.

## Repeating or generating for an area

| | `atelier/pattern` | `atelier/field` |
| --- | --- | --- |
| Signature | geometry alone | geometry plus width and height |
| Output | one `<pattern>` in `<defs>` | a `<g>` inline |
| Applied through | `fill="url(#id)"` | it is the content |
| Output size | fixed for a given tile | depends on generator, dimensions and sampling |
| Resizing the surface | the tile repeats | scale the SVG, or regenerate to recompute geometry |

Some motifs exist on both sides, and that is not a duplication to resolve. `Pattern::dots()` is
an even lattice that repeats; a radial halftone varies the dot radius across the area and
cannot. `Pattern::voronoi()` is computed on a torus so it joins; a bounded voronoi is free at
its edges and can relax sites toward the arithmetic means of their cell vertices.

The name carries the motif. The package carries the contract.
