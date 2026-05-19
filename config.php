<?php
declare(strict_types=1);

/**
 * Back-compat entry: all application bootstrap lives in bootstrap.php.
 * Existing require_once 'config.php' / '../config.php' keeps working without Apache changes.
 */
require_once __DIR__ . '/bootstrap.php';
