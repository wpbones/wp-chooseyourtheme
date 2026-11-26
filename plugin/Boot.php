<?php

namespace WPChooseYourTheme;

use WPChooseYourTheme\Admin\SettingsPage;
use WPChooseYourTheme\Support\BackLink;
use WPChooseYourTheme\Support\Block;
use WPChooseYourTheme\Support\Migration;
use WPChooseYourTheme\Support\Shortcode;
use WPChooseYourTheme\Support\Visitor;
use WPChooseYourTheme\Widgets\ThemeSwitcherWidget;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * Every hook of the plugin, in one place.
 */
final class Boot
{
  public static function hooks(string $pluginFile): void
  {
    // The settings of a site updated from 0.x, before anything reads them.
    add_action('plugins_loaded', [Migration::class, 'run']);

    // Apply the visitor's choice. WordPress reads the active theme before `init`, and these are lazy:
    // without a cookie they return what they are given.
    add_filter('template', [Visitor::class, 'filterTemplate']);
    add_filter('stylesheet', [Visitor::class, 'filterStylesheet']);
    // The same for the options core reads directly, as the Customizer's theme preview does.
    add_filter('pre_option_stylesheet', [Visitor::class, 'preStylesheet']);
    add_filter('pre_option_template', [Visitor::class, 'preTemplate']);
    add_filter('pre_option_current_theme', [Visitor::class, 'preCurrentTheme']);
    add_filter('pre_option_stylesheet_root', [Visitor::class, 'preStylesheetRoot']);
    add_filter('pre_option_template_root', [Visitor::class, 'preTemplateRoot']);
    add_action('send_headers', [Visitor::class, 'sendHeaders']);

    add_action('init', [self::class, 'init'], 0);
    add_action('init', [Visitor::class, 'handleRequest'], 1);
    add_action('init', [Block::class, 'register']);
    add_action('widgets_init', [self::class, 'widgets']);
    // Late, after the theme's own footer output, so a switcher printed there has been seen.
    add_action('wp_footer', [BackLink::class, 'print'], 99);

    SettingsPage::hooks($pluginFile);
  }

  public static function init(): void
  {
    // WP Bones loads the text domain on init as well, later; the widget is registered before it.
    load_plugin_textdomain('wp-chooseyourtheme', false, dirname(plugin_basename(dirname(__DIR__) . '/wp-chooseyourtheme.php')) . '/languages');

    Shortcode::register();
  }

  public static function widgets(): void
  {
    register_widget(ThemeSwitcherWidget::class);
  }
}
