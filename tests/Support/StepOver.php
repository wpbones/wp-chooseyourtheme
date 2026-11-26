<?php

declare(strict_types=1);

namespace WPChooseYourTheme\Tests\Support;

use WPChooseYourTheme\Support\Visitor;

/**
 * Visitor with its three side effects, the cookie, the redirect and the exit, recorded instead.
 */
final class StepOver extends Visitor
{
  /** @var array<int, array{0: string, 1: int}> */
  public static array $cookies = [];

  /** @var string[] */
  public static array $redirects = [];

  protected static function sendCookie(string $value, int $expires): void
  {
    self::$cookies[] = [$value, $expires];
  }

  protected static function redirect(string $location): void
  {
    self::$redirects[] = $location;
  }
}
