<?php

/**
 * Plugin Name: WP Choose Your Theme
 * Plugin URI: https://wordpress.org/plugins/wp-chooseyourtheme/
 * Description: Let your visitors choose which of your installed themes they see the site with: a dropdown, a list or a single "next theme" link, by template tag, shortcode or widget.
 * Version: 1.0.0
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Author: Giovambattista Fazioli
 * Author URI: http://undolog.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-chooseyourtheme
 * Domain Path: languages
 */

if (!defined('ABSPATH')) {
  exit();
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our application. We just need to utilize it! We'll simply require it
| into the script here so that we don't have to worry about manual
| loading any of our classes later on. It feels nice to relax.
|
*/

require_once __DIR__ . '/bootstrap/autoload.php';

/*
|--------------------------------------------------------------------------
| Early hooks
|--------------------------------------------------------------------------
|
| WordPress asks for the active theme before `init`, so the filters that
| apply the visitor's choice cannot wait for a service provider: they are
| registered here, as soon as the plugin loads. Everything they do is lazy.
|
*/

\WPChooseYourTheme\Boot::hooks(__FILE__);
