<?php

namespace WPChooseYourTheme\Support;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * The script of the menu mode: it submits the form when the dropdown changes, and hides the button.
 * Without it the button does the same job.
 */
final class Assets
{
  public const HANDLE = 'wp-chooseyourtheme';

  public static function enqueue(): void
  {
    $file = dirname(__DIR__, 2) . '/public/js/wp-chooseyourtheme.js';

    wp_enqueue_script(
      self::HANDLE,
      plugins_url('public/js/wp-chooseyourtheme.js', dirname(__DIR__, 2) . '/wp-chooseyourtheme.php'),
      [],
      is_file($file) ? (string) filemtime($file) : false,
      ['in_footer' => true, 'strategy' => 'defer']
    );
  }
}
