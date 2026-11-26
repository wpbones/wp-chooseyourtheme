<?php

namespace WPChooseYourTheme\Support;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * The plugin's one option: which installed themes a visitor may pick, and for how long the pick is
 * remembered. Nothing else is stored; the visitor's own choice lives in a cookie.
 */
final class Settings
{
  /** The option row, an array: {@see self::DEFAULTS}. */
  public const OPTION = 'wp_chooseyourtheme_settings';

  /** The row version 0.x stored its hidden-themes list in. */
  public const LEGACY_OPTION = 'wp-chooseyourtheme';

  public const MIN_DAYS = 1;
  public const MAX_DAYS = 365;

  public const DEFAULTS = [
    // Stylesheet slugs. The active theme is always available and need not be listed.
    'allowed_themes' => [],
    // How many days the browser remembers the choice.
    'cookie_days' => 30,
  ];

  /**
   * The saved settings over the defaults, normalised: a corrupted row never reaches the callers.
   *
   * @return array{allowed_themes: string[], cookie_days: int}
   */
  public static function get(): array
  {
    $saved = get_option(self::OPTION, []);

    return self::normalize(is_array($saved) ? $saved : []);
  }

  /**
   * @return string[] Stylesheet slugs the administrator allows.
   */
  public static function allowedThemes(): array
  {
    return self::get()['allowed_themes'];
  }

  public static function cookieDays(): int
  {
    return self::get()['cookie_days'];
  }

  /**
   * What the Settings API stores: only strings, only slugs of installed themes, a lifetime inside
   * the limits.
   *
   * @param mixed    $input     What the form posted.
   * @param string[] $installed Stylesheet slugs of the themes installed now.
   * @return array{allowed_themes: string[], cookie_days: int}
   */
  public static function sanitize(mixed $input, array $installed): array
  {
    $input = is_array($input) ? $input : [];

    $allowed = [];
    if (isset($input['allowed_themes']) && is_array($input['allowed_themes'])) {
      foreach ($input['allowed_themes'] as $slug) {
        if (is_string($slug) && in_array($slug, $installed, true)) {
          $allowed[] = $slug;
        }
      }
    }

    return self::normalize([
      'allowed_themes' => $allowed,
      'cookie_days' => $input['cookie_days'] ?? self::DEFAULTS['cookie_days'],
    ]);
  }

  /**
   * @param array<string, mixed> $row
   * @return array{allowed_themes: string[], cookie_days: int}
   */
  public static function normalize(array $row): array
  {
    $allowed = [];
    if (isset($row['allowed_themes']) && is_array($row['allowed_themes'])) {
      foreach ($row['allowed_themes'] as $slug) {
        if (is_string($slug) && $slug !== '') {
          $allowed[$slug] = $slug;
        }
      }
    }

    $days = $row['cookie_days'] ?? self::DEFAULTS['cookie_days'];
    $days = is_numeric($days) ? (int) $days : self::DEFAULTS['cookie_days'];

    return [
      'allowed_themes' => array_values($allowed),
      'cookie_days' => max(self::MIN_DAYS, min(self::MAX_DAYS, $days)),
    ];
  }
}
