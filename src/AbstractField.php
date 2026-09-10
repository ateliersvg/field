<?php

declare(strict_types=1);

namespace Atelier\Field;

use Atelier\Field\Internal\Num;
use Atelier\Svg\Document;
use Atelier\Svg\Dumper\CompactXmlDumper;
use Atelier\Svg\Element\ElementInterface;
use Atelier\Svg\Element\Shape\RectElement;
use Atelier\Svg\Element\Structural\GroupElement;

/**
 * Shared behaviour of every field: theme, assembly of the group, and the three
 * ways out of the library.
 *
 * Subclasses hold the geometry and describe the shapes. Colour lives here and
 * applies on top, so a factory only ever takes measurements and a seed.
 *
 * The theme property is not readonly so with*() can clone; the class is
 * immutable by contract, not by keyword.
 */
abstract class AbstractField implements FieldInterface
{
    private ?Theme $theme = null;

    abstract public function width(): float;

    abstract public function height(): float;

    /**
     * @return list<ElementInterface>
     */
    abstract public function shapes(): array;

    public function theme(): Theme
    {
        return $this->theme ?? Theme::default();
    }

    public function withTheme(Theme $theme): static
    {
        $clone = clone $this;
        $clone->theme = $theme;

        return $clone;
    }

    /**
     * Changes the foreground only; a nonempty palette still controls toned fills.
     */
    public function withColor(string $color): static
    {
        return $this->withTheme($this->theme()->with(foregroundColor: $color));
    }

    /**
     * Shortcut painting a ground behind the drawing. Null leaves the surface
     * transparent, which is the default.
     */
    public function withBackground(?string $color): static
    {
        $theme = $this->theme();

        return $this->withTheme(new Theme($theme->foregroundColor, $color, $theme->palette, $theme->opacity));
    }

    public function withOpacity(?float $opacity): static
    {
        $theme = $this->theme();

        return $this->withTheme(new Theme($theme->foregroundColor, $theme->backgroundColor, $theme->palette, $opacity));
    }

    public function element(): GroupElement
    {
        $theme = $this->theme();
        $group = new GroupElement();

        if (null !== $theme->opacity) {
            $group->setAttribute('opacity', Num::format($theme->opacity));
        }

        if (null !== $theme->backgroundColor) {
            $group->appendChild($this->ground($theme->backgroundColor));
        }

        foreach ($this->shapes() as $shape) {
            $group->appendChild($shape);
        }

        return $group;
    }

    public function toSvg(): string
    {
        $document = Document::create($this->width(), $this->height());
        $document->getRootElement()?->setAttribute('viewBox', '0 0 '.Num::format($this->width()).' '.Num::format($this->height()));
        $document->getRootElement()?->appendChild($this->element());

        // Document::toString() is a stub in atelier/svg 1.0 and returns an empty
        // root. Serialisation goes through a dumper.
        $dumper = new CompactXmlDumper();
        $dumper->includeXmlDeclaration(false);

        return $dumper->dump($document);
    }

    public function toDataUri(): string
    {
        return 'data:image/svg+xml,'.rawurlencode($this->toSvg());
    }

    public function toCss(): string
    {
        return \sprintf(
            "background-image: url(\"%s\");\nbackground-repeat: no-repeat;\nbackground-size: cover;",
            $this->toDataUri(),
        );
    }

    /**
     * Paints a shape with the foreground.
     *
     * @template T of ElementInterface
     *
     * @param T $shape
     *
     * @return T
     */
    final protected function painted(ElementInterface $shape): ElementInterface
    {
        $shape->setAttribute('fill', $this->theme()->foregroundColor);

        return $shape;
    }

    /**
     * Paints one layer of a stack.
     *
     * With a palette, the layer takes its own colour at full strength. Without
     * one, it takes the foreground at the depth the field asks for, which is
     * how a single ink still reads as several planes.
     *
     * @template T of ElementInterface
     *
     * @param T     $shape
     * @param int   $layer index of the layer, from the back
     * @param float $depth opacity to fall back on when the theme carries no palette
     *
     * @return T
     */
    final protected function toned(ElementInterface $shape, int $layer, float $depth): ElementInterface
    {
        $theme = $this->theme();
        $shape->setAttribute('fill', $theme->colorAt($layer));

        if (!$theme->hasPalette()) {
            $shape->setAttribute('fill-opacity', Num::format($depth));
        }

        return $shape;
    }

    /**
     * Outlines a shape with the foreground at the given thickness.
     *
     * @template T of ElementInterface
     *
     * @param T $shape
     *
     * @return T
     */
    final protected function outlined(ElementInterface $shape, float $thickness): ElementInterface
    {
        $shape->setAttribute('fill', 'none');
        $shape->setAttribute('stroke', $this->theme()->foregroundColor);
        $shape->setAttribute('stroke-width', Num::format($thickness));

        return $shape;
    }

    private function ground(string $color): RectElement
    {
        $ground = new RectElement();
        $ground->setX('0')
            ->setY('0')
            ->setWidth(Num::format($this->width()))
            ->setHeight(Num::format($this->height()));
        $ground->setAttribute('fill', $color);

        return $ground;
    }
}
