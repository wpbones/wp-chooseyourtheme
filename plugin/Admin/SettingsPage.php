<?php

namespace WPChooseYourTheme\Admin;

use WPChooseYourTheme\Support\Settings;
use WPChooseYourTheme\Support\Themes;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * Appearance › WP Choose Your Theme: which installed themes a visitor may pick, and for how long the
 * pick is remembered. Saved through the Settings API, which checks the capability and the nonce.
 */
final class SettingsPage
{
  public const SLUG = 'wp-chooseyourtheme';
  public const GROUP = 'wp_chooseyourtheme';
  public const CAPABILITY = 'manage_options';

  public static function hooks(string $pluginFile): void
  {
    add_action('admin_menu', [self::class, 'menu']);
    add_action('admin_init', [self::class, 'register']);
    add_filter('plugin_action_links_' . plugin_basename($pluginFile), [self::class, 'actionLinks']);
  }

  public static function menu(): void
  {
    add_theme_page(
      __('WP Choose Your Theme', 'wp-chooseyourtheme'),
      __('WP Choose Your Theme', 'wp-chooseyourtheme'),
      self::CAPABILITY,
      self::SLUG,
      [self::class, 'render']
    );
  }

  public static function register(): void
  {
    register_setting(self::GROUP, Settings::OPTION, [
      'type' => 'array',
      'sanitize_callback' => [self::class, 'sanitize'],
      'default' => Settings::DEFAULTS,
      'show_in_rest' => false,
    ]);
  }

  /**
   * @param mixed $input
   * @return array{allowed_themes: string[], cookie_days: int, back_link: bool}
   */
  public static function sanitize($input): array
  {
    return Settings::sanitize($input, array_map('strval', array_keys(wp_get_themes())));
  }

  /**
   * @param string[] $links
   * @return string[]
   */
  public static function actionLinks(array $links): array
  {
    array_unshift($links, '<a href="' . esc_url(admin_url('themes.php?page=' . self::SLUG)) . '">' . esc_html__('Settings', 'wp-chooseyourtheme') . '</a>');

    return $links;
  }

  public static function render(): void
  {
    if (!current_user_can(self::CAPABILITY)) {
      return;
    }

    $active = Themes::activeSlug();
    $activeTheme = wp_get_theme($active);
    $settings = Settings::get();
    $rows = [];

    foreach (wp_get_themes(['errors' => false, 'allowed' => true]) as $slug => $theme) {
      $slug = (string) $slug;
      $isActive = $slug === $active;
      $sameKind = $isActive || Themes::sameKind($theme, $activeTheme);

      $rows[] = [
        'slug' => $slug,
        'name' => (string) $theme->get('Name'),
        'version' => (string) $theme->get('Version'),
        'kind' => Themes::kind($theme),
        'parent' => $theme->parent() ? (string) $theme->parent()->get('Name') : '',
        'active' => $isActive,
        'selectable' => $sameKind,
        'checked' => $isActive || in_array($slug, $settings['allowed_themes'], true),
      ];
    }

    usort($rows, static fn ($a, $b) => strnatcasecmp($a['name'], $b['name']));

    settings_errors();

    \WPChooseYourTheme()
      ->view('settings')
      ->with([
        'group' => self::GROUP,
        'option' => Settings::OPTION,
        'rows' => $rows,
        'cookieDays' => $settings['cookie_days'],
        'backLink' => $settings['back_link'],
        'minDays' => Settings::MIN_DAYS,
        'maxDays' => Settings::MAX_DAYS,
        'activeKind' => Themes::kind($activeTheme),
      ])
      ->render();
  }
}
