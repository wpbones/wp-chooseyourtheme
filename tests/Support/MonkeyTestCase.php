<?php

declare(strict_types=1);

namespace WPChooseYourTheme\Tests\Support;

use Brain\Monkey;
use Mockery;
use PHPUnit\Framework\TestCase;
use WPChooseYourTheme\Support\Visitor;

/**
 * Brain Monkey is set up and torn down around every test; the request globals and the visitor's
 * per-request decision are reset so nothing leaks from one test into the next.
 */
abstract class MonkeyTestCase extends TestCase
{
  protected function setUp(): void
  {
    parent::setUp();
    Monkey\setUp();

    $_GET = $_COOKIE = $_REQUEST = [];
    $_SERVER['REQUEST_URI'] = '/';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    unset($GLOBALS['pagenow']);
    $GLOBALS['wpcyt_test_blog'] = 1;
    Visitor::reset();

    // The unit tests have no WordPress; these are the plain behaviours the code leans on.
    Monkey\Functions\stubs([
      'wp_unslash' => static fn ($value) => is_string($value) ? stripslashes($value) : $value,
      'trailingslashit' => static fn ($value) => rtrim((string) $value, '/\\') . '/',
      'wp_parse_url' => static fn ($url, $component = -1) => parse_url((string) $url, $component),
      'rest_get_url_prefix' => 'wp-json',
      'get_current_blog_id' => static fn () => $GLOBALS['wpcyt_test_blog'],
      'get_raw_theme_root' => static fn ($slug, $skip = false) => '/themes',
      'esc_html' => static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES),
      'esc_attr' => static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES),
      'esc_url' => static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES),
      'esc_html__' => static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES),
      '__' => static fn ($value) => $value,
    ]);
  }

  protected function tearDown(): void
  {
    // A Brain Monkey expectation is a Mockery expectation, which PHPUnit does not count as an
    // assertion: without this a test made only of expect()->once() is reported as risky.
    $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
    Monkey\tearDown();
    parent::tearDown();
  }
}
