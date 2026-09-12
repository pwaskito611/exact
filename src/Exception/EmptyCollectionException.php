<?php

declare(strict_types=1);

namespace Exact\Exception;

use RuntimeException;

final class EmptyCollectionException extends RuntimeException
{
    public function __construct(
        string $message = 'Cannot perform this operation on an empty collection.'
    ) {
        parent::__construct($message);
    }
}