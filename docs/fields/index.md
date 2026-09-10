---
title: Fields
description: Eighteen procedural SVG drawings for specified dimensions, with styles and parameter variants.
order: 20
---

# Every Field

Eighteen drawings, one factory each. Each is generated for the dimensions its factory names
and repeats nothing. Painted coverage varies: some fill the frame, others leave transparent space.

Every figure below is the field its page documents, at the size that page shows.

<div class="figure-grid figure-grid--large">
<figure><a href="waves.md"><img src="../images/waves.svg" alt="Layered sine bands closed to the bottom of the frame"></a><figcaption><a href="waves.md">waves</a></figcaption></figure>
<figure><a href="dunes.md"><img src="../images/dunes.svg" alt="Ridges of sand, smooth on the rise and long in the hollow"></a><figcaption><a href="dunes.md">dunes</a></figcaption></figure>
<figure><a href="strata.md"><img src="../images/strata.svg" alt="Sedimentary beds of unequal thickness"></a><figcaption><a href="strata.md">strata</a></figcaption></figure>
<figure><a href="mountains.md"><img src="../images/mountains.svg" alt="Ranges of straight segments, crests alternating with saddles"></a><figcaption><a href="mountains.md">mountains</a></figcaption></figure>
<figure><a href="skyline.md"><img src="../images/skyline.svg" alt="A city in silhouette, blocks of unequal width and height"></a><figcaption><a href="skyline.md">skyline</a></figcaption></figure>
<figure><a href="cloud.md"><img src="../images/cloud.svg" alt="A flat underside and a row of puffs above it"></a><figcaption><a href="cloud.md">cloud</a></figcaption></figure>
<figure><a href="blob.md"><img src="../images/blob.svg" alt="One closed curve with no straight edge"></a><figcaption><a href="blob.md">blob</a></figcaption></figure>
<figure><a href="contours.md"><img src="../images/contours.svg" alt="Isolines read off a height field"></a><figcaption><a href="contours.md">contours</a></figcaption></figure>
<figure><a href="archipelago.md"><img src="../images/archipelago.svg" alt="Islands drawn as filled level sets"></a><figcaption><a href="archipelago.md">archipelago</a></figcaption></figure>
<figure><a href="low-poly.md"><img src="../images/low-poly.svg" alt="A frame cut into triangles, each one flat and shaded"></a><figcaption><a href="low-poly.md">lowPoly</a></figcaption></figure>
<figure><a href="voronoi.md"><img src="../images/voronoi.svg" alt="The cells of a Voronoi partition"></a><figcaption><a href="voronoi.md">voronoi</a></figcaption></figure>
<figure><a href="foam.md"><img src="../images/foam.svg" alt="Discs packed with a bounded attempt budget"></a><figcaption><a href="foam.md">foam</a></figcaption></figure>
<figure><a href="flow-field.md"><img src="../images/flow-field.svg" alt="Lines traced through a field of directions"></a><figcaption><a href="flow-field.md">flowField</a></figcaption></figure>
<figure><a href="terrain.md"><img src="../images/terrain.svg" alt="A lattice laid in perspective over a relief"></a><figcaption><a href="terrain.md">terrain</a></figcaption></figure>
<figure><a href="halftone.md"><img src="../images/halftone.svg" alt="A lattice of dots whose radius falls away from one point"></a><figcaption><a href="halftone.md">halftone</a></figcaption></figure>
<figure><a href="moire.md"><img src="../images/moire.svg" alt="Two rulings crossing at a small angle"></a><figcaption><a href="moire.md">moire</a></figcaption></figure>
<figure><a href="wave-interference.md"><img src="../images/wave-interference.svg" alt="Concentric rings spreading from several sources"></a><figcaption><a href="wave-interference.md">waveInterference</a></figcaption></figure>
<figure><a href="rays.md"><img src="../images/rays.svg" alt="Wedges spreading from a point"></a><figcaption><a href="rays.md">rays</a></figcaption></figure>
</div>

## Choosing one

| You want | Use |
| --- | --- |
| water, or any horizon that reads as depth | [waves](waves.md) |
| a soft relief with no visible period | [dunes](dunes.md) |
| a hard relief, angular and distant | [mountains](mountains.md) |
| a city, or any silhouette of right angles | [skyline](skyline.md) |
| one soft mass over a flat base | [cloud](cloud.md) |
| one closed shape with no corner at all | [blob](blob.md) |
| a map, or any relief drawn as line | [contours](contours.md) |
| flat facets, calm and faceted | [lowPoly](low-poly.md) |
| an organic mesh, closer to cracked glaze than to a rule | [voronoi](voronoi.md) |
| a current, drawn as line | [flowField](flow-field.md) |
| a tone that varies across the surface | [halftone](halftone.md) |
| a pattern that belongs to neither of the two things drawing it | [moire](moire.md), [waveInterference](wave-interference.md) |
| rock, read as a section rather than as a landscape | [strata](strata.md) |
| a surface with a depth axis, drawn in perspective | [terrain](terrain.md) |
| a map, with the land filled rather than outlined | [archipelago](archipelago.md) |
| a light, or anything spreading from one point | [rays](rays.md) |
| discs of every size, packed rather than laid out | [foam](foam.md) |

## Three that take no seed

[halftone](halftone.md), [moire](moire.md) and [rays](rays.md) are ruled rather than drawn. Their
arguments say everything about the result, so running one twice gives the same drawing without a
seed having to promise it.
