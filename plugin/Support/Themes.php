<?php

namespace WPChooseYourTheme\Support;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * Which themes a visitor may choose. A theme counts only when it is installed and valid (no missing
 * parent, no missing stylesheet), allowed on the site, and, unless a filter says otherwise, of the
 * same kind as the active theme: block themes and classic themes keep their templates, menus and
 * widget areas in different places, so mixing them at run time gives a broken page.
 */
final class Themes
{
  /**
   * The site's active theme: the option itself, not get_stylesheet(), which this plugin filters.
   */
  public static function activeSlug(): string
  {
    return (string) get_option('stylesheet');
  }

  /**
   * Whether a visitor may be shown the theme with this stylesheet slug. The active theme always may.
   */
  public static function isSelectable(string $slug): bool
  {
    if ($slug === '') {
      return false;
    }

    $active = self::activeSlug();

    if ($slug === $active) {
      return true;
    }

    if (!in_array($slug, Settings::allowedThemes(), true)) {
      return false;
    }

    $theme = wp_get_theme($slug);

    return self::isUsable($theme) && self::sameKind($theme, wp_get_theme($active));
  }

  /**
   * The WP_Theme for a slug, or null when it is not installed.
   */
  public static function get(string $slug): ?object
  {
    $theme = wp_get_theme($slug);

    return $theme->exists() ? $theme : null;
  }

  /**
   * The themes to offer, by stylesheet slug, in the order of their names: the active theme first
   * of all by name, like the others, and every allowed theme that passes the same test as
   * {@see self::isSelectable()}.
   *
   * @return array<string, \WP_Theme|object>
   */
  public static function selectable(): array
  {
    $active = self::activeSlug();
    $allowed = Settings::allowedThemes();
    $activeTheme = wp_get_theme($active);
    $offer = [];

    foreach (wp_get_themes(['errors' => false, 'allowed' => true]) as $slug => $theme) {
      $slug = (string) $slug;

      if ($slug === $active || (in_array($slug, $allowed, true) && self::isUsable($theme) && self::sameKind($theme, $activeTheme))) {
        $offer[$slug] = $theme;
      }
    }

    // wp_get_themes() leaves the active theme out only when it is broken; it stays reachable.
    if (!isset($offer[$active]) && $activeTheme->exists()) {
      $offer[$active] = $activeTheme;
    }

    uasort($offer, static fn ($a, $b) => strnatcasecmp((string) $a->get('Name'), (string) $b->get('Name')));

    return $offer;
  }

  /**
   * Installed, no errors (a child theme whose parent is gone has one), and allowed on this site
   * (on a network, WP_Theme::is_allowed() says which themes the site may use).
   */
  public static function isUsable(object $theme): bool
  {
    if (!$theme->exists()) {
      return false;
    }

    $errors = $theme->errors();

    if ($errors !== false && $errors !== null) {
      return false;
    }

    return (bool) $theme->is_allowed();
  }

  /**
   * Block and classic themes mix only on the administrator's own risk, through the filter.
   */
  public static function sameKind(object $theme, object $active): bool
  {
    $same = (bool) $theme->is_block_theme() === (bool) $active->is_block_theme();

    /**
     * Whether a visitor may be switched between a block theme and a classic theme.
     *
     * The two kinds keep templates, template parts, menus and widget areas in different places, so a
     * site that mixes them is a site that has been tested by hand.
     *
     * @param bool   $allow Whether to allow it. Default false.
     * @param object $theme The theme the visitor asked for.
     * @param object $active The site's active theme.
     */
    return $same || (bool) apply_filters('wp_chooseyourtheme_allow_mixed_kinds', false, $theme, $active);
  }

  /**
   * "block" or "classic", for the settings page.
   */
  public static function kind(object $theme): string
  {
    return $theme->is_block_theme() ? 'block' : 'classic';
  }
}
