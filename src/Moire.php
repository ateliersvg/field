<?php

declare(strict_types=1);

namespace Atelier\Field;

use Atelier\Field\Exception\InvalidArgumentException;
use Atelier\Field\Internal\Clip;
use Atelier\Field\Internal\Guard;
use Atelier\Field\Internal\Num;
use Atelier\Svg\Element\PathElement;

/**
 * Two rulings crossing at a small angle, and the pattern that appears between
 * them.
 *
 * Neither ruling holds a figure. What the eye reads is the third pattern their
 * crossing makes: where two lines nearly coincide the ink doubles, where they
 * fall between one another it spreads, and the beat between those two states
 * sweeps across the frame at a period far longer than either spacing.
 *
 * That beat is why the angle has to stay small. Past a few degrees the two
 * rulings simply read as a crosshatch, and the interference disappears into it.
 *
 * It is also why the line width is given as a share of the spacing rather than
 * on its own. The beat is a change in how much ink covers the surface, so a
 * ruling drawn as thin strokes over a wide gap has almost no coverage to change
 * and shows almost no beat. Around four tenths, the two states are half ink and
 * nearly full ink, and the pattern is at its loudest.
 *
 * The rulings are laid along the diagonal so they still cover the frame once
 * turned, and every line is then cut to the frame by geometry. A field fills
 * exactly the box it was given, whether it is rendered on its own or appended
 * to a document that is larger.
 */
final class Moire extends AbstractField
{
    private function __construct(
        private readonly float $width,
        private readonly float $height,
        private readonly float $spacing,
        private readonly float $angle,
        private readonly float $thickness,
    ) {
    }

    /**
     * @param float $width   area to cover
     * @param float $height  area to cover
     * @param float $spacing distance between two lines of one ruling
     * @param float $angle   angle between the two rulings, in degrees, at most 20
     * @param float $duty    share of the spacing a line covers, (0, 1)
     *
     * @throws InvalidArgumentException if the area is not positive or the angle is too open to beat
     */
    public static function create(float $width = 600.0, float $height = 400.0, float $spacing = 5.0, float $angle = 4.0, float $duty = 0.30): self
    {
        Guard::positive($width, 'width');
        Guard::positive($height, 'height');
        Guard::positive($spacing, 'spacing');
        Guard::atMost($spacing, min($width, $height) / 6, 'spacing', 'a sixth of the smaller side');
        Guard::positive($angle, 'angle');
        Guard::atMost($angle, 20.0, 'angle', '20 degrees, past which the rulings read as a crosshatch');
        Guard::positive($duty, 'duty');
        Guard::fraction($duty, 'duty');

        return new self($width, $height, $spacing, $angle, $spacing * $duty);
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
        return [
            $this->outlined($this->ruling(0.0), $this->thickness),
            $this->outlined($this->ruling(deg2rad($this->angle)), $this->thickness),
        ];
    }

    /**
     * One ruling, turned about the middle of the frame.
     *
     * Lines are laid out along the diagonal so the ruling still covers the
     * frame once it is turned, and the corners keep their ink.
     */
    private function ruling(float $angle): PathElement
    {
        $centreX = $this->width / 2;
        $centreY = $this->height / 2;
        $reach = hypot($this->width, $this->height) / 2;

        // One line past each end, so a ruling always overshoots the frame and
        // the cut decides where it stops. Ending on the last whole spacing
        // instead leaves a gutter of up to one spacing against an edge, which
        // reads as a defect beside a ruling this dense.
        $first = -$reach - $this->spacing;
        $lines = (int) ceil((2 * $reach + 2 * $this->spacing) / $this->spacing);
        $cos = cos($angle);
        $sin = sin($angle);
        $path = '';

        for ($line = 0; $line <= $lines; ++$line) {
            $offset = $first + $line * $this->spacing;

            // A line of the unturned ruling runs vertically at x = offset, from
            // -reach to +reach. Turning it about the centre keeps it straight,
            // so the two ends are enough.
            $ends = [];

            foreach ([-$reach, $reach] as $along) {
                $ends[] = $centreX + $offset * $cos - $along * $sin;
                $ends[] = $centreY + $offset * $sin + $along * $cos;
            }

            $inside = Clip::segment($ends[0], $ends[1], $ends[2], $ends[3], $this->width, $this->height);

            if (null === $inside) {
                continue;
            }

            $path .= 'M'.Num::format($inside[0]).' '.Num::format($inside[1])
                .'L'.Num::format($inside[2]).' '.Num::format($inside[3]);
        }

        $ruling = new PathElement();
        $ruling->setD($path);

        return $ruling;
    }
}
