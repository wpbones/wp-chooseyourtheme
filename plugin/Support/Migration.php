<?php

namespace WPChooseYourTheme\Support;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * Version 0.x kept a list of HIDDEN themes, by theme name, in the `wp-chooseyourtheme` option.
 * Version 1.0 keeps a list of ALLOWED themes, by stylesheet slug. A site that used 0.x keeps what its
 * visitors could pick: every installed theme that was not hidden becomes allowed.
 */
final class Migration
{
  /**
   * Run once, on the first load after the update (WordPress.org updates do not fire the activation
   * hook). A site with settings already stored is left alone; the old option stays in the database
   * until the plugin is uninstalled, so a downgrade finds it.
   */
  public static function run(): void
  {
    if (get_option(Settings::OPTION, null) !== null) {
      return;
    }

    $legacy = get_option(Settings::LEGACY_OPTION, null);

    if (!is_array($legacy)) {
      // A new installation: nothing to carry over, the defaults apply.
      add_option(Settings::OPTION, Settings::DEFAULTS);

      return;
    }

    add_option(Settings::OPTION, Settings::normalize([
      'allowed_themes' => self::allowedFrom($legacy, wp_get_themes()),
      'cookie_days' => Settings::DEFAULTS['cookie_days'],
    ]));
  }

  /**
   * @param array<string, mixed>                  $legacy The 0.x option row.
   * @param array<string, \WP_Theme|object>       $themes Installed themes, by stylesheet slug.
   * @return string[] Stylesheet slugs of the themes that were not hidden.
   */
  public static function allowedFrom(array $legacy, array $themes): array
  {
    $hidden = isset($legacy['hidden_themes']) && is_array($legacy['hidden_themes']) ? $legacy['hidden_themes'] : [];
    $hidden = array_filter($hidden, 'is_string');

    $allowed = [];
    foreach ($themes as $slug => $theme) {
      // 0.x stored the theme's name; a directory name is accepted too, as 0.x's own fallback did.
      if (in_array((string) $theme->get('Name'), $hidden, true) || in_array((string) $slug, $hidden, true)) {
        continue;
      }

      $allowed[] = (string) $slug;
    }

    return $allowed;
  }
}
