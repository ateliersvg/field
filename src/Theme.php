<?php

declare(strict_types=1);

namespace Atelier\Field;

use Atelier\Field\Exception\InvalidArgumentException;

/**
 * The colours a field is drawn with.
 *
 * Three roles and nothing else. The foreground marks the surface and defaults
 * to currentColor, so a drawing follows the colour of whatever it sits in. The
 * background is left out entirely unless asked for, so a field drops onto any
 * surface. The palette serves the fields that separate layers or cells; a field
 * needing one colour never reads it.
 *
 * The palette is a ramp, not a series. Chart themes carry categorical colours
 * because two series must be told apart; a stack of layers must instead read as
 * depth, so the colours run in one direction and are applied back to front. A
 * categorical palette on a layered field reads as stripes.
 *
 * The shape mirrors Atelier\Chart\Theme\Theme and Atelier\Diagram\Theme\Theme:
 * named presets, colorAt() and with(). One vocabulary across the packages means
 * a document is themed once.
 */
final readonly class Theme
{
    /**
     * @param list<string> $palette a ramp read back to front, one colour per layer, empty to separate the layers by opacity alone
     *
     * @throws InvalidArgumentException if a colour is blank or the opacity leaves [0, 1]
     */
    public function __construct(
        public string $foregroundColor = 'currentColor',
        public ?string $backgroundColor = null,
        public array $palette = [],
        public ?float $opacity = null,
    ) {
        foreach ([$foregroundColor, $backgroundColor, ...$palette] as $color) {
            if (null !== $color && '' === trim($color)) {
                throw new InvalidArgumentException('Field theme colors must not be empty.');
            }
        }

        if (null !== $opacity && (!is_finite($opacity) || $opacity < 0.0 || $opacity > 1.0)) {
            throw new InvalidArgumentException(\sprintf('opacity must be between 0 and 1, got %s.', var_export($opacity, true)));
        }
    }

    /**
     * One ink inherited from the host, no ground. What a field draws with when
     * nothing is said.
     */
    public static function default(): self
    {
        return new self();
    }

    public static function dark(): self
    {
        return new self('#48c5ff', '#010205', ['#48c5ff', '#3aa0d6', '#2c7cae', '#1f5985', '#13375c']);
    }

    public static function mono(): self
    {
        return new self('#111111', '#ffffff', ['#c6c6c6', '#9a9a9a', '#6b6b6b', '#3d3d3d', '#111111']);
    }

    public static function blueprint(): self
    {
        return new self('#8fd3ff', '#0b2d4d', ['#8fd3ff', '#5fa8d8', '#3f7fae', '#2b5c82', '#1c3f5c']);
    }

    public static function neutral(): self
    {
        return new self('#4a5057', '#f6f5f1', ['#c2c7cc', '#a0a7ae', '#7e868e', '#5c646d', '#3a424b']);
    }

    /**
     * The colour of one layer, wrapping round the palette.
     *
     * Falls back to the foreground when no palette is set, so a field keeps one
     * ink and tells its layers apart by opacity alone.
     */
    public function colorAt(int $index): string
    {
        $count = \count($this->palette);

        if (0 === $count) {
            return $this->foregroundColor;
        }

        return $this->palette[(($index % $count) + $count) % $count];
    }

    /**
     * Whether the layers carry colours of their own.
     */
    public function hasPalette(): bool
    {
        return [] !== $this->palette;
    }

    /**
     * Derives a theme, keeping every role that is not overridden.
     *
     * @param list<string>|null $palette
     */
    public function with(
        ?string $foregroundColor = null,
        ?string $backgroundColor = null,
        ?array $palette = null,
        ?float $opacity = null,
    ): self {
        return new self(
            $foregroundColor ?? $this->foregroundColor,
            $backgroundColor ?? $this->backgroundColor,
            $palette ?? $this->palette,
            $opacity ?? $this->opacity,
        );
    }
}
