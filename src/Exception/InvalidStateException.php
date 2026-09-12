<?php

declare(strict_types=1);

namespace Exact\Exception;

use RuntimeException;

final class InvalidStateException extends RuntimeException
{
    public function __construct(
        string $message = 'The object is in an invalid state.'
    ) {
        parent::__construct($message);
    }
}