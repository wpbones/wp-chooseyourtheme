<?php

if (!defined('ABSPATH')) {
  return;
}

/*
|--------------------------------------------------------------------------
| Template tag
|--------------------------------------------------------------------------
|
| wp_chooseyourtheme() has been the plugin's public PHP function since 0.1:
| it prints the theme switcher.
|
*/

if (!function_exists('wp_chooseyourtheme')) {
  /**
   * Print the theme switcher.
   *
   * @param string|array $args Query string or array. `mode`: "menu" (default, a dropdown), "list" (a list
   *                           of links) or "roll" (one link to the next theme). Any other value is "menu".
   * @return void
   */
  function wp_chooseyourtheme($args = ''): void
  {
    $args = wp_parse_args($args, ['mode' => \WPChooseYourTheme\Support\Renderer::DEFAULT_MODE]);

    // Built and escaped by the renderer.
    echo \WPChooseYourTheme\Support\Renderer::render(\WPChooseYourTheme\Support\Renderer::normalizeMode($args['mode']));
  }
}
