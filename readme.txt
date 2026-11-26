# WP Choose Your Theme
Contributors: gfazioli
Donate link: https://www.paypal.com/donate/?hosted_button_id=TBPD4R78VDMCJ
Tags: theme, switcher, visitors, block, widget
Requires at least: 6.6
Tested up to: 7.2
Stable tag: 1.1.0
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Let your visitors choose which of your installed themes they see the site with: a dropdown, a list or a single "next theme" link.

## Description

WP Choose Your Theme lets a visitor pick the theme they see your site with, from the themes you installed and allowed. Their choice is remembered in their own browser; you, your other visitors and wp-admin keep using the active theme.

Documentation and source code: [github.com/wpbones/wp-chooseyourtheme](https://github.com/wpbones/wp-chooseyourtheme).

Put the switcher where you want it, in four ways and three looks:

* **Block** — "Theme Switcher", in the block editor and in the Site Editor (templates and template parts). The editor shows the real switcher; the sidebar sets the mode and an optional title, and the block follows your text alignment, font size, colours and margin settings. It turns a `[wp_chooseyourtheme]` shortcode or the plugin's widget into a block with one click.
* **Widget** — Appearance › Widgets, "Choose Your Theme".
* **Shortcode** — `[wp_chooseyourtheme mode="list"]`, in a post, a page or a text block.
* **Template tag** — `<?php wp_chooseyourtheme( 'mode=roll' ); ?>`, in a theme file such as `sidebar.php`.

The three modes:

* `menu` (the default) — a dropdown. It works without JavaScript, with a button; with JavaScript it changes the theme as soon as you pick one.
* `list` — a list of links, one per theme; the current one has the class `selected`.
* `roll` — one link to the next theme, in alphabetical order, going back to the first after the last.

= A way back =

A template part or a widget area belongs to one theme. If the switcher sits in your active theme's footer, a visitor who picked another theme gets that theme's own footer, which may have no switcher, and could be stuck with that look. So, on a page that shows another theme than your own and has no switcher on it, a small bar at the bottom says "You are viewing this site with Twenty Twenty-Four. Back to Twenty Twenty-Five" and the link brings the visitor back, to the same page. It never shows when a switcher is on the page, to a visitor who sees your own theme, in wp-admin or in print. You can turn it off in Appearance › WP Choose Your Theme, or per page with the `wp_chooseyourtheme_show_back_link` filter.

= Safe by default =

* **A new site offers nothing but its active theme.** Open Appearance › WP Choose Your Theme and tick the themes you want visitors to be able to pick. A theme you keep installed for other reasons (an old one, one you are working on) is never public unless you say so.
* **A visitor can only ever get a theme you allowed.** The browser stores the theme's folder name; anything else, a theme you did not allow, a theme that is not installed, a child theme whose parent is missing, is ignored and the visitor sees the active theme.
* **Only the public site changes.** wp-admin, the REST API, Ajax, cron, the login screen, the Customizer and theme previews always use the active theme.
* **Block and classic themes are not mixed.** They keep templates, menus and widget areas in different places, so a visitor is only offered themes of the same kind as your active theme. (A developer can lift this with the `wp_chooseyourtheme_allow_mixed_kinds` filter.)
* The choice is a cookie with `SameSite=Lax` and `HttpOnly` (and `Secure` on HTTPS), with the lifetime you set, 30 days by default.

### Child themes

A child theme is offered like any other theme; the visitor gets the child and, through it, its parent.

### Page caching

A page cache keeps one copy of a page and serves it to everyone, so it can serve a visitor the wrong theme. The plugin sends `Cache-Control: no-cache` and `Vary: Cookie` on every request that carries the choice cookie, and the redirect that sets the choice is not cacheable either. If your caching plugin or host cache ignores those headers, set it to skip the cache for visitors who send the `wpcyt_theme` cookie.

## Installation

1. Upload the entire content of the plugin archive to your `/wp-content/plugins/` directory, so that everything is in `/wp-content/plugins/wp-chooseyourtheme/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Open Appearance › WP Choose Your Theme and tick the themes visitors may choose.
4. Add the block, the widget, the shortcode or the template tag where you want the switcher.

## Screenshots

1. Appearance › WP Choose Your Theme: the installed themes, their kind and whether visitors may choose them, and how long the choice is remembered
2. A visitor switched to Twenty Twenty-Four with the dropdown (menu mode, through the shortcode)
3. List mode: a link to every theme visitors may choose

## FAQ

= 1. Does it change the theme of my site? =
No. The active theme stays what it is, for you and for every visitor who does not choose. A choice shows only to the visitor who made it, in that browser.

= 2. Which themes does a visitor see in the switcher? =
Your active theme, plus the installed themes you ticked in Appearance › WP Choose Your Theme, that have no errors and are of the same kind (block or classic) as the active one. If that leaves only one theme, the switcher shows nothing.

= 3. A visitor picked a theme and sees an empty sidebar or no menu. =
A visitor sees the chosen theme with that theme's own settings: its Customizer options (logo, colours, header) and its menu locations, exactly as if it were active. A theme that has never been set up on your site has none yet, so it starts with the theme's defaults and no menu assigned. Set it up once: activate it, configure it, and activate your own theme back. Widgets are shared by name: a widget area that has the same ID in both themes shows the same widgets.

= 4. Can visitors pick a block theme on a classic site, or the other way round? =
Not by default, see "Safe by default". A developer can allow it with the `wp_chooseyourtheme_allow_mixed_kinds` filter, on their own tests: the two kinds do not share widget areas, and a block theme has no classic widget areas or template files to call the template tag from (the shortcode works in any post or page).

= 5. The visitor's choice seems ignored, or they see someone else's theme. =
A page cache is serving a stored copy. See "Page caching" above.

= 6. What does the cookie hold? =
The folder name of the theme (for example `twentytwentyfive`) and nothing else, no identifier, no personal data. It is named `wpcyt_theme`.

= 7. I used 0.x. What happens to my settings? =
If 0.x had stored its settings on your site (it did when a wp-admin page first loaded after you activated it), the first time 1.0 loads every installed theme you had not hidden becomes an allowed theme. A site with no stored 0.x settings is treated as a new installation: only the active theme is offered until you tick others. Visitors' old cookies (named `wpcyt_name`) are no longer read: each visitor picks again, once. The template tag `wp_chooseyourtheme()` and its three modes work as before.

= 8. Can I change how it looks? =
The output has plain classes: `wpcyt_form`, `wpcyt_combo`, `wpcyt_submit`, `wpcyt_list`, `wpcyt_roll`, and on each list item the theme's name as a class, plus `selected` for the current one. The block is wrapped in `wp-block-wp-chooseyourtheme-switcher`, and has the block editor's own typography, colour and spacing settings.

= 9. Where do I put the switcher? =
In a **block theme**, in the Site Editor: a Theme Switcher block in a template part (header, footer) or in a template, or in the content of a page or a post. In a **classic theme**, in a widget area (Appearance › Widgets), in a theme file with the template tag, or in the content of a page, post or text widget with the shortcode.

A template part or a widget area belongs to **one theme**: the footer you edited in Twenty Twenty-Five is not the footer of Twenty Twenty-Four, and two classic themes may name their widget areas differently. So either put the switcher in each theme you allow, or put it in page content, which every theme shows. For whatever is left, the "way back" bar (see above) lets a visitor return to your own theme.

= 10. What is the bar at the bottom of the page? =
The "way back" bar, shown only to a visitor who chose another theme, on a page with no switcher. Turn it off in Appearance › WP Choose Your Theme, or in code: `add_filter( 'wp_chooseyourtheme_show_back_link', '__return_false' );`. The filter also receives the theme the visitor is shown and your active theme, so you can switch it off for some pages or themes.

= 11. Can I request a new feature? =
Yes! Please submit feature requests at [the support forum](https://wordpress.org/support/plugin/wp-chooseyourtheme/).

= 12. Can I report a bug? =
Yes! Please report bugs at [the support forum](https://wordpress.org/support/plugin/wp-chooseyourtheme/).

## Changelog

### 1.1.0

* New: the **Theme Switcher block** (`wp-chooseyourtheme/switcher`) for the block editor and the Site Editor: a live preview, the mode and an optional title in the sidebar, alignment, font size, colour and margin settings. A shortcode or the plugin's widget turns into the block with one click. The shortcode, the widget and the template tag work as before
* New: **a way back**. A visitor who chose another theme, on a page with no switcher (a template part or a widget area belongs to one theme, so the theme they chose may not have it), sees a small bar at the bottom with a link back to your own theme. On by default, also after updating from 1.0.0; turn it off in Appearance › WP Choose Your Theme, or with the `wp_chooseyourtheme_show_back_link` filter
* New: the widget can be read by the block editor, for the one-click conversion to the block
* Changed: where to put the switcher in block and classic themes is answered in the FAQ
* Changed: translations for the block and the new setting, in Italian, Spanish, French, German and Brazilian Portuguese

### 1.0.0

A rewrite of the plugin for current WordPress, on the [WP Bones](https://wpbones.com) framework. If you are updating from 0.x, read this.

**What stays**

* The template tag `wp_chooseyourtheme()` and its modes `menu`, `list` and `roll`, with the same CSS classes (`wpcyt_combo`, `wpcyt_list`, `wpcyt_roll`, the theme name on each list item, `selected` on the current one)
* Appearance › WP Choose Your Theme is still where you configure it
* Your settings, if 0.x stored them: every theme you had not hidden becomes an allowed theme the first time 1.0 loads. A site without stored 0.x settings is treated as a new installation

**What changed**

* Requires WordPress 6.6 and PHP 8.1 (0.x did not work on PHP 8: its classes used PHP 4 constructors, which PHP 8 no longer calls, and it relied on `get_themes()` and `get_current_theme()`, deprecated since WordPress 3.4)
* New: the choice is stored by theme folder name, in a cookie with `SameSite=Lax` and `HttpOnly`, instead of by theme name in a cookie written by JavaScript. Visitors choose again once: 0.x cookies are not read
* New: a theme is applied only if it is allowed, installed and without errors; the active theme is used otherwise. Child themes are applied with their parent
* New: it applies on the public site only, never in wp-admin, the REST API, Ajax, cron, the login screen, the Customizer or theme previews
* New: block themes and classic themes are not mixed: only themes of the same kind as the active one are offered
* New: the choice works without JavaScript, with a link or a form that redirects back to the same page; the dropdown submits itself when JavaScript is on
* New: the shortcode `[wp_chooseyourtheme mode="menu|list|roll"]` and a classic widget
* New: you set how many days the choice is remembered (30 by default)
* New: pages for visitors who made a choice are sent with `no-cache` and `Vary: Cookie`, so a page cache does not mix visitors up
* Changed: you now list the themes that are **allowed**, not the ones that are hidden. A new installation allows no theme but the active one
* Changed: the settings are saved with a nonce and the `manage_options` capability, and every value is validated
* Changed: every string is translatable; Italian, Spanish, French, German and Brazilian Portuguese are included
* Changed: the plugin removes its options when you delete it
* Removed: the `rand` mode, which 0.x listed as a future idea and never implemented

### 0.6

* Rev HTML output for list and roll mode: add slug theme name class

### 0.5

* Add Admin in backend under Appearance. Rev code and avoid Javascript.

### 0.2

* Rev

### 0.1

* First release

## Upgrade Notice

### 1.1.0
Adds the Theme Switcher block and a small "way back" bar for visitors who chose another theme on a page with no switcher. The bar is on by default; turn it off in Appearance › WP Choose Your Theme.

### 1.0.0
A rewrite: it needs WordPress 6.6 and PHP 8.1. The template tag and the settings carry over (themes that were not hidden become allowed); visitors choose their theme again once. Block and classic themes are no longer mixed.
