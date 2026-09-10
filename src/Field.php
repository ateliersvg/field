<?php

declare(strict_types=1);

namespace Atelier\Field;

/**
 * Entry point to the catalogue.
 *
 * Every factory takes the area to cover, then its own geometry, then a seed.
 * Colour comes after, through withTheme(), withColor() and withBackground().
 *
 * The width and height in every signature are what separates this package from
 * atelier/pattern: a field is drawn for one surface and does not repeat.
 */
final class Field
{
    /**
     * Layered sine bands closed to the bottom of the frame, read as water.
     */
    public static function waves(float $width, float $height, int $layers = 5, int $seed = 1): Waves
    {
        return Waves::create($width, $height, $layers, $seed);
    }

    /**
     * Ridges of sand read from a noise field, smooth on the rise and long in
     * the hollow.
     */
    public static function dunes(float $width, float $height, int $layers = 4, int $seed = 1): Dunes
    {
        return Dunes::create($width, $height, $layers, $seed);
    }

    /**
     * Ranges of straight segments, a crest alternating with a saddle.
     */
    public static function mountains(float $width, float $height, int $layers = 4, int $seed = 1): Mountains
    {
        return Mountains::create($width, $height, $layers, $seed);
    }

    /**
     * A city in silhouette: blocks of unequal width and height, right angles
     * only.
     */
    public static function skyline(float $width, float $height, int $layers = 2, int $seed = 1): Skyline
    {
        return Skyline::create($width, $height, $layers, $seed);
    }

    /**
     * A lattice of dots whose radius falls away from one point, the way a
     * printed halftone renders a lit sphere.
     */
    public static function halftone(float $width, float $height, float $spacing = 13.0, float $focusX = 0.36, float $focusY = 0.36): Halftone
    {
        return Halftone::create($width, $height, $spacing, $focusX, $focusY);
    }

    /**
     * Isolines read off a height field, the way a map draws relief.
     */
    public static function contours(float $width, float $height, float $cell = 8.0, int $levels = 12, float $thickness = 1.0, int $seed = 1): Contours
    {
        return Contours::create($width, $height, $cell, $levels, $thickness, $seed);
    }

    /**
     * A frame cut into triangles, each one flat and shaded by the height under
     * it.
     */
    public static function lowPoly(float $width, float $height, float $cell = 60.0, int $tones = 6, int $seed = 1): LowPoly
    {
        return LowPoly::create($width, $height, $cell, $tones, $seed);
    }

    /**
     * The cells of a Voronoi partition, relaxed toward their own middles and
     * cut against the frame.
     */
    public static function voronoi(float $width, float $height, int $sites = 24, int $relax = 2, float $thickness = 1.2, int $tones = 6, int $seed = 1): Voronoi
    {
        return Voronoi::create($width, $height, $sites, $relax, $thickness, $tones, $seed);
    }

    /**
     * Lines traced through a field of directions, the way iron filings lie
     * along a magnet.
     */
    public static function flowField(float $width, float $height, int $lines = 260, int $length = 60, float $thickness = 1.0, int $seed = 1): FlowField
    {
        return FlowField::create($width, $height, $lines, $length, $thickness, $seed);
    }

    /**
     * Two rulings crossing at a small angle, and the pattern that appears
     * between them.
     */
    public static function moire(float $width, float $height, float $spacing = 5.0, float $angle = 4.0, float $duty = 0.30): Moire
    {
        return Moire::create($width, $height, $spacing, $angle, $duty);
    }

    /**
     * Concentric rings spreading from several sources, the way rain marks still
     * water.
     */
    public static function waveInterference(float $width, float $height, int $sources = 3, float $spacing = 13.0, float $thickness = 0.9, int $seed = 1): WaveInterference
    {
        return WaveInterference::create($width, $height, $sources, $spacing, $thickness, $seed);
    }

    /**
     * One closed curve with no straight edge and no obvious centre.
     */
    public static function blob(float $width, float $height, int $nodes = 7, float $irregularity = 0.28, int $seed = 1): Blob
    {
        return Blob::create($width, $height, $nodes, $irregularity, $seed);
    }

    /**
     * A row of puffs over a flat base, the shape a cloud is drawn as.
     */
    public static function cloud(float $width, float $height, int $puffs = 5, int $seed = 1): Cloud
    {
        return Cloud::create($width, $height, $puffs, $seed);
    }

    /**
     * A section through sedimentary rock: beds of unequal thickness, dipping
     * across the frame.
     */
    public static function strata(float $width, float $height, int $beds = 8, float $dip = 5.0, int $seed = 1): Strata
    {
        return Strata::create($width, $height, $beds, $dip, $seed);
    }

    /**
     * A lattice laid in perspective over a relief, receding to a horizon.
     */
    public static function terrain(float $width, float $height, int $rows = 15, int $columns = 19, float $horizon = 0.36, float $relief = 0.6, float $thickness = 0.9, int $seed = 1): Terrain
    {
        return Terrain::create($width, $height, $rows, $columns, $horizon, $relief, $thickness, $seed);
    }

    /**
     * Islands: the filled level sets of a height field, the way a map shades
     * land above successive altitudes.
     */
    public static function archipelago(float $width, float $height, float $cell = 8.0, int $levels = 3, int $seed = 1): Archipelago
    {
        return Archipelago::create($width, $height, $cell, $levels, $seed);
    }

    /**
     * Wedges spreading from a point, which is usually better placed outside the
     * frame than inside it.
     */
    public static function rays(float $width, float $height, int $rays = 16, float $focusX = 0.5, float $focusY = 1.08, float $duty = 0.5): Rays
    {
        return Rays::create($width, $height, $rays, $focusX, $focusY, $duty);
    }

    /**
     * Discs packed until there is no room left, largest near one point.
     */
    public static function foam(float $width, float $height, float $radius = 22.0, float $focusX = 0.3, float $focusY = 0.28, int $tones = 5, int $seed = 1): Foam
    {
        return Foam::create($width, $height, $radius, $focusX, $focusY, $tones, $seed);
    }
}
