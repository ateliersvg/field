<?php

declare(strict_types=1);

namespace Atelier\Field;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Internal\Clip;
use Atelier\Field\Internal\Guard;
use Atelier\Field\Internal\Num;
use Atelier\Svg\Element\PathElement;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * Concentric rings spreading from several sources, the way rain marks still
 * water.
 *
 * Each source draws its own rings at a constant spacing. What the drawing is
 * about is where two sets cross: the pattern of lens shapes between them is the
 * interference, and it belongs to no single source.
 *
 * Rings run to the far corner, so a source near an edge still reaches the
 * other side rather than fading into a disc, and each ring is then cut to the
 * frame by geometry. This clips sampled centrelines; their strokes can extend
 * half their width past the frame when appended to a larger document.
 */
final class WaveInterference extends AbstractField
{
    /** Distance between two samples along a ring, in user units. */
    private const float SAMPLE = 3.0;

    private function __construct(
        private readonly float $width,
        private readonly float $height,
        private readonly int $sources,
        private readonly float $spacing,
        private readonly float $thickness,
        private readonly int $seed,
    ) {
    }

    /**
     * @param float $width     area to cover
     * @param float $height    area to cover
     * @param int   $sources   points the rings spread from, 1 to 24
     * @param float $spacing   distance between two rings of one source
     * @param float $thickness line width
     * @param int   $seed      where the sources stand
     *
     * @throws InvalidArgumentException if the area is not positive or a count is out of range
     */
    public static function create(float $width = 600.0, float $height = 400.0, int $sources = 3, float $spacing = 13.0, float $thickness = 0.9, int $seed = 1): self
    {
        Guard::positive($width, 'width');
        Guard::positive($height, 'height');
        Guard::atLeast($sources, 1, 'sources');
        Guard::atMost((float) $sources, 24.0, 'sources', '24');
        Guard::positive($spacing, 'spacing');
        Guard::atMost($spacing, min($width, $height) / 4, 'spacing', 'a quarter of the smaller side');
        Guard::positive($thickness, 'thickness');

        return new self($width, $height, $sources, $spacing, $thickness, $seed);
    }

    public function width(): float
    {
        return $this->width;
    }

    public function height(): float
    {
        return $this->height;
    }

    public function shapes(): array
    {
        $random = new Randomizer(new Xoshiro256StarStar($this->seed));
        $shapes = [];

        for ($source = 0; $source < $this->sources; ++$source) {
            // Sources keep clear of the border, where most of their rings would
            // fall outside the frame and the crossing would have nowhere to
            // happen.
            $x = $random->getFloat(0.15, 0.85) * $this->width;
            $y = $random->getFloat(0.15, 0.85) * $this->height;

            $reach = max(
                hypot($x, $y),
                hypot($this->width - $x, $y),
                hypot($x, $this->height - $y),
                hypot($this->width - $x, $this->height - $y),
            );

            for ($ring = 1; $ring * $this->spacing <= $reach; ++$ring) {
                $path = $this->ring($x, $y, $ring * $this->spacing);

                if ('' === $path) {
                    continue;
                }

                $arc = new PathElement();
                $arc->setD($path);

                $shapes[] = $this->outlined($arc, $this->thickness);
            }
        }

        return $shapes;
    }

    /**
     * One ring, as the arcs of it lying inside the frame.
     *
     * The ring is sampled at a fixed step along its own circumference, so a
     * wide ring is cut into as many points as it needs and a narrow one is not
     * paid for in coordinates it does not use.
     */
    private function ring(float $x, float $y, float $radius): string
    {
        $steps = max(24, (int) ceil(2 * M_PI * $radius / self::SAMPLE));
        $points = [];

        for ($step = 0; $step <= $steps; ++$step) {
            $angle = 2 * M_PI * $step / $steps;

            $points[] = [$x + cos($angle) * $radius, $y + sin($angle) * $radius];
        }

        $path = '';

        foreach (Clip::runs($points, $this->width, $this->height) as $run) {
            foreach ($run as $index => [$pointX, $pointY]) {
                $path .= (0 === $index ? 'M' : 'L').Num::format($pointX).' '.Num::format($pointY);
            }
        }

        return $path;
    }
}
