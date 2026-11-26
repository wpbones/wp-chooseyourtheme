<?php

namespace WPChooseYourTheme\Support;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * [wp_chooseyourtheme mode="menu|list|roll"]
 */
final class Shortcode
{
  public const TAG = 'wp_chooseyourtheme';

  public static function register(): void
  {
    add_shortcode(self::TAG, [self::class, 'render']);
  }

  /**
   * @param array<string, string>|string $atts
   */
  public static function render($atts = []): string
  {
    $atts = shortcode_atts(['mode' => Renderer::DEFAULT_MODE], is_array($atts) ? $atts : [], self::TAG);

    return Renderer::render(Renderer::normalizeMode($atts['mode']));
  }
}
