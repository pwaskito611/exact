<?php

declare(strict_types=1);

require_once __DIR__ . '/Vec.php';

if (!class_exists('Exact\\Collection\\Seq', false)) {
    class_alias(\Exact\Collection\Vec::class, 'Exact\\Collection\\Seq');
}
