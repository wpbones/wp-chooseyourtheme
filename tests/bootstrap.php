<?php

/**
 * Unit-suite bootstrap: no WordPress, no database.
 *
 * WordPress functions are stubbed per test with Brain Monkey, so a test that reaches one that was
 * not stubbed fails with a clear "undefined function" instead of silently passing.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
  define('ABSPATH', __DIR__ . '/');
}

if (!defined('DAY_IN_SECONDS')) {
  define('DAY_IN_SECONDS', 86400);
}

require dirname(__DIR__) . '/vendor/autoload.php';
