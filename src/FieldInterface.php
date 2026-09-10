<?php

declare(strict_types=1);

namespace Atelier\Field;

use Atelier\Svg\Element\ElementInterface;
use Atelier\Svg\Element\Structural\GroupElement;

/**
 * A drawing generated for a specified coordinate frame.
 *
 * A field is the opposite of a tile. A tiling makes every point of the surface
 * equivalent: no top, no bottom, no centre, no light. Anything carrying a
 * horizon or a gradient of density therefore cannot tile, by construction and
 * not for want of effort, and lives here instead.
 *
 * The consequence is in the signature: a field is told its width and height,
 * but painted coverage and sampling cost depend on the generator. Some leave
 * transparent regions. A tile repeats without changing its geometry.
 */
interface FieldInterface
{
    /**
     * Width of the coordinate frame, in user units.
     */
    public function width(): float;

    /**
     * Height of the coordinate frame, in user units.
     */
    public function height(): float;

    /**
     * The shapes of the drawing, already painted, in paint order.
     *
     * @return list<ElementInterface>
     */
    public function shapes(): array;

    /**
     * The whole drawing in one group, ready to append to a document.
     */
    public function element(): GroupElement;

    /**
     * A standalone document with dimensions and a matching viewBox.
     */
    public function toSvg(): string;

    /**
     * The drawing as a data URI, for a CSS background.
     *
     * A background image is a document of its own, so currentColor resolves to
     * black there. Set a real foreground on the theme before calling this.
     */
    public function toDataUri(): string;

    /**
     * The CSS declarations painting this drawing as a background.
     */
    public function toCss(): string;
}
