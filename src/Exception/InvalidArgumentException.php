<?php

declare(strict_types=1);

namespace Atelier\Field\Exception;

/**
 * Thrown when a field receives geometry or colours it cannot draw with.
 */
final class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
}
