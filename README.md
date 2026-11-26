# WP Choose Your Theme

Let your visitors choose which of your installed themes they see the site with: a dropdown, a list or a single "next theme" link.

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/wp-chooseyourtheme)](https://wordpress.org/plugins/wp-chooseyourtheme/)
[![WordPress Plugin Required PHP Version](https://img.shields.io/wordpress/plugin/required-php/wp-chooseyourtheme)](https://wordpress.org/plugins/wp-chooseyourtheme/)
[![WordPress Plugin Tested WP Version](https://img.shields.io/wordpress/plugin/tested/wp-chooseyourtheme)](https://wordpress.org/plugins/wp-chooseyourtheme/)
[![License](https://img.shields.io/badge/license-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

## Overview

Pick, in Appearance › WP Choose Your Theme, which installed themes a visitor may choose; the visitor then sees the site with that theme, and nobody else does.

Built on the [WPBones](https://wpbones.com) framework.

## Links

- [WordPress.org Plugin Page](https://wordpress.org/plugins/wp-chooseyourtheme/)
- [Support Forum](https://wordpress.org/support/plugin/wp-chooseyourtheme/)
- [Changelog](readme.txt#changelog)

## Features

A widget, a shortcode and a template tag, with three modes each.

You tick, in **Appearance › WP Choose Your Theme**, the installed themes a visitor may pick. The visitor picks one from a switcher you place with a widget, a shortcode or a template tag; the choice is stored in their browser, and from then on that browser sees the site with that theme. The active theme stays what it is for you, wp-admin and every other visitor.

```
[wp_chooseyourtheme mode="menu"]   a dropdown (default)
[wp_chooseyourtheme mode="list"]   a list of links
[wp_chooseyourtheme mode="roll"]   one link to the next theme, round to the first
```

```php
<?php wp_chooseyourtheme( 'mode=list' ); ?>
```

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
php bones               # Bones CLI
php bones deploy <path> --wp --no-build   # package for WordPress.org (no JavaScript is built, so no yarn install)
```

Layout: `wp-chooseyourtheme.php` (header and early hooks), `plugin/Support` (settings, themes, the visitor's choice, rendering, shortcode), `plugin/Admin` (settings page), `plugin/Widgets`, `resources/views/settings.php`, `uninstall.php`.

## License

GPL-2.0-or-later.
