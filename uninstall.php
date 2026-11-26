<?php

/**
 * Runs when the plugin is deleted from the Plugins screen (not when it is deactivated): removes
 * every option it stored, on every site of a network. A visitor's own choice is a cookie in their
 * browser; there is nothing about it on the server.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit();
}

(static function (): void {
  $options = [
    // The settings of 1.x, and the list of hidden themes 0.x kept (left in place by the migration).
    'wp_chooseyourtheme_settings',
    'wp-chooseyourtheme',
    // The widget's instances.
    'widget_wp_chooseyourtheme',
    // WP Bones' own rows: its options model and its migration state.
    'wp_choose_your_theme_slug',
    'wp_choose_your_theme_slug_db_version',
    'wp_choose_your_theme_slug_migrations',
    'wp_choose_your_theme_slug_migrations_failure',
    'wp_choose_your_theme_slug_migrations.lock',
  ];

  $delete = static function () use ($options): void {
    foreach ($options as $option) {
      delete_option($option);
    }
  };

  if (is_multisite()) {
    foreach (get_sites(['fields' => 'ids', 'number' => 0]) as $siteId) {
      switch_to_blog((int) $siteId);
      $delete();
      restore_current_blog();
    }

    return;
  }

  $delete();
})();
