<?php

declare(strict_types=1);

require_once __DIR__ . '/NonEmptyVec.php';

if (!class_exists('Exact\\Collection\\NonEmptySeq', false)) {
    class_alias(\Exact\Collection\NonEmptyVec::class, 'Exact\\Collection\\NonEmptySeq');
}
