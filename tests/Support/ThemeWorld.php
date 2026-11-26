<?php

declare(strict_types=1);

namespace WPChooseYourTheme\Tests\Support;

use Brain\Monkey\Functions;
use WPChooseYourTheme\Support\Settings;

/**
 * A site, for a test: the installed themes, the active one and the saved settings.
 */
trait ThemeWorld
{
  /**
   * @param FakeTheme[]          $themes
   * @param array<string, mixed> $settings
   */
  protected function site(string $active, array $themes, array $settings = []): void
  {
    $bySlug = [];
    foreach ($themes as $theme) {
      $bySlug[$theme->slug] = $theme;
    }

    Functions\when('get_option')->alias(function ($name, $default = false) use ($active, $settings) {
      return match ($name) {
        'stylesheet' => $active,
        Settings::OPTION => $settings,
        default => $default,
      };
    });

    Functions\when('wp_get_theme')->alias(
      static fn ($slug = '') => $bySlug[$slug] ?? new FakeTheme((string) $slug, exists: false)
    );

    Functions\when('wp_get_themes')->alias(
      static fn () => array_filter($bySlug, static fn (FakeTheme $theme) => $theme->exists && !$theme->broken)
    );

    Functions\when('get_stylesheet')->justReturn($active);
  }
}
