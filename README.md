# WP Choose Your Theme

Let your visitors choose which of your installed themes they see the site with: a dropdown, a list or a single "next theme" link.

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/wp-chooseyourtheme)](https://wordpress.org/plugins/wp-chooseyourtheme/)
[![WordPress Plugin Required PHP Version](https://img.shields.io/wordpress/plugin/required-php/wp-chooseyourtheme)](https://wordpress.org/plugins/wp-chooseyourtheme/)
[![WordPress Plugin Tested WP Version](https://img.shields.io/wordpress/plugin/tested/wp-chooseyourtheme)](https://wordpress.org/plugins/wp-chooseyourtheme/)
[![License](https://img.shields.io/badge/license-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Try it in WordPress Playground](https://img.shields.io/badge/Try%20it-WordPress%20Playground-3858e9?logo=wordpress&logoColor=white)](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/wpbones/wp-chooseyourtheme/main/assets/blueprints/blueprint.json)

<div align="center">
  <p><img src="https://raw.githubusercontent.com/wpbones/.github/main/glancecard/wp-chooseyourtheme.svg" width="880" alt="WP Choose Your Theme at a glance, refreshed daily: commits, releases, stars, issues and pull requests, a year of commit activity, the weekly rhythm, languages, latest commits and releases, contributors" /><br>
  <sub>Made with <a href="https://github.com/gfazioli/glancecard">glancecard</a> · refreshed daily</sub></p>
</div>

## Overview

Pick, in Appearance › WP Choose Your Theme, which installed themes a visitor may choose; the visitor then sees the site with that theme, and nobody else does.

Built on the [WPBones](https://wpbones.com) framework.

## Links

- [WordPress.org Plugin Page](https://wordpress.org/plugins/wp-chooseyourtheme/)
- [Support Forum](https://wordpress.org/support/plugin/wp-chooseyourtheme/)
- [Changelog](readme.txt#changelog)

## Features

A block, a widget, a shortcode and a template tag, with three modes each.

You tick, in **Appearance › WP Choose Your Theme**, the installed themes a visitor may pick. The visitor picks one from a switcher you place with a widget, a shortcode or a template tag; the choice is stored in their browser, and from then on that browser sees the site with that theme. The active theme stays what it is for you, wp-admin and every other visitor.

The **Theme Switcher** block (`wp-chooseyourtheme/switcher`) shows the real switcher in the block editor and the Site Editor; its sidebar sets the mode and an optional title, and it supports text alignment, font size, text and link colour and margin. A `[wp_chooseyourtheme]` shortcode or the plugin's widget converts to it. There is no JavaScript build: the editor script is `blocks/switcher/index.js`, written by hand against the `wp.*` globals.

```
[wp_chooseyourtheme mode="menu"]   a dropdown (default)
[wp_chooseyourtheme mode="list"]   a list of links
[wp_chooseyourtheme mode="roll"]   one link to the next theme, round to the first
```

```php
<?php wp_chooseyourtheme( 'mode=list' ); ?>
```

### A way back

A template part or a widget area belongs to one theme: a switcher in Twenty Twenty-Five's footer is not in Twenty Twenty-Four's, and a visitor who picked the latter would be stuck with it for the cookie's lifetime. So, at `wp_footer`, a small bar ("You are viewing this site with X. Back to Y") is printed when all of these hold: the public site, the visitor is shown a theme other than the active one, no switcher (block, shortcode, widget, template tag) was printed on the page, the setting "Link back" is on (default, also after an upgrade from 1.0.0) and the filter allows it:

```php
add_filter( 'wp_chooseyourtheme_show_back_link', function ( bool $show, $shown_theme, $active_theme ) {
  return $show && ! is_front_page();
}, 10, 3 );
```

The link is the plugin's own choice link to the active theme, which is always allowed, so it returns to the same page.

In a block theme put the switcher in each allowed theme's template part, or in page content; in a classic theme, in each theme's widget area, or in content.

### Rules

- **Allowed themes only.** The cookie `wpcyt_theme` holds a theme's folder name. A theme that is not allowed, not installed, or with errors (a child whose parent is gone) is ignored: the visitor sees the active theme. A new site allows only the active theme.
- **Public site only.** Never wp-admin, REST, Ajax, cron, `wp-login.php`, the Customizer or a theme preview.
- **Same kind only.** Block and classic themes are not mixed (the `wp_chooseyourtheme_allow_mixed_kinds` filter lifts it).
- **Child themes** set both `template` and `stylesheet`.
- **Cookie:** `SameSite=Lax`, `HttpOnly`, `Secure` on HTTPS, lifetime in days (default 30).
- **No JavaScript needed:** the choice is a link or a GET form to `?wpcyt_theme=<slug>`, answered by a redirect to the same page without it. A small script submits the dropdown on change.
- **Caching:** a page cache would serve one visitor's theme to everyone. Requests that carry the cookie get `Cache-Control: no-cache` and `Vary: Cookie`; set your cache to bypass visitors with the `wpcyt_theme` cookie if it ignores them.

## Requirements

- PHP >= 8.1
- WordPress >= 6.6
- Composer (development only)

## Installation

Install it from the WordPress.org plugin directory, or copy a deploy package to `wp-content/plugins/wp-chooseyourtheme/` and activate it.

## Development

```bash
composer install
composer test           # PHPUnit + Brain Monkey, no WordPress needed
tests/live/probe.sh <plugin folder> <WordPress folder> <classic|block|mixed>   # live probe in a local Playground (see the script header)
php bones               # Bones CLI
php bones deploy <path> --wp --no-build   # package for WordPress.org (no JavaScript is built, so no yarn install)
```

Layout: `wp-chooseyourtheme.php` (header and early hooks), `plugin/Support` (settings, themes, the visitor's choice, rendering, shortcode, block, way-back bar), `plugin/Admin` (settings page), `plugin/Widgets`, `blocks/switcher` (the block: `block.json`, `index.js`, `index.asset.php`, `style.css`), `resources/views/settings.php`, `uninstall.php`. After changing a string in the block's JavaScript, run `yarn make-pot`, update the `.po` files and `wp i18n make-mo languages && wp i18n make-json languages --no-purge`; the editor script's translations are loaded from `languages/` by `Block::register()`.

## License

GPL-2.0-or-later.
