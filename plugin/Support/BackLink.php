<?php

namespace WPChooseYourTheme\Support;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * The way back. A template part or a widget area belongs to one theme: a visitor who chose Twenty
 * Twenty-Four may land on a site whose Twenty Twenty-Four footer has no switcher, and would be stuck
 * with that look for as long as the cookie lives. So, at the end of a public page that shows another
 * theme than the site's own and has no switcher on it, a small bar says so and links back.
 *
 * The link is the ordinary choice link, to the site's active theme, which is always allowed.
 */
final class BackLink
{
  /**
   * `wp_footer`: print the bar, if there is a reason to.
   */
  public static function print(): void
  {
    // Built, and escaped, by html().
    echo self::html();
  }

  public static function html(): string
  {
    // Cheapest tests first: a site without visitors' choices stops at the cookie.
    if (Renderer::printed() || !Settings::backLink()) {
      return '';
    }

    // Only a visitor who is shown another theme than the site's own: the same decision that swaps it.
    $slug = Visitor::slug();

    if ($slug === null) {
      return '';
    }

    $chosen = Themes::get($slug);
    $own = Themes::get(Themes::activeSlug());

    if ($chosen === null || $own === null) {
      return '';
    }

    /**
     * Whether to show the way-back bar to a visitor who sees another theme than the site's own, on a
     * page with no switcher. The plugin has already checked that all of that is true.
     *
     * @param bool   $show   Whether to show it. Default true (the setting is on).
     * @param object $chosen The theme the visitor is shown.
     * @param object $own    The site's active theme.
     */
    if (!apply_filters('wp_chooseyourtheme_show_back_link', true, $chosen, $own)) {
      return '';
    }

    $uri = Url::relative((string) wp_unslash($_SERVER['REQUEST_URI'] ?? '/'));
    $href = Url::with($uri, Visitor::PARAM, Themes::activeSlug());

    /* translators: %s: the name of the theme the visitor is shown. */
    $sentence = sprintf(__('You are viewing this site with %s.', 'wp-chooseyourtheme'), (string) $chosen->get('Name'));
    /* translators: %s: the name of the site's own theme. */
    $link = sprintf(__('Back to %s', 'wp-chooseyourtheme'), (string) $own->get('Name'));

    return '<style id="wpcyt-back-style">' . self::CSS . '</style>'
      . '<aside class="wpcyt_back" aria-label="' . esc_attr__('Theme choice', 'wp-chooseyourtheme') . '"><p>'
      . esc_html($sentence) . ' <a href="' . esc_url($href) . '" rel="nofollow">' . esc_html($link) . '</a></p></aside>';
  }

  /**
   * Its own colours, so it reads on a light page and on a dark one; the theme's font; logical
   * properties, for right-to-left sites; no animation; not printed.
   */
  private const CSS = '.wpcyt_back{position:fixed;inset-block-end:0;inset-inline:0;z-index:99999;box-sizing:border-box;'
    . 'padding:.5em 1em;background:#1d2327;color:#fff;border-block-start:1px solid rgba(255,255,255,.35);'
    . 'font:inherit;font-size:.875rem;line-height:1.4;text-align:center}'
    . '.wpcyt_back p{margin:0;color:inherit;font:inherit}'
    . '.wpcyt_back a{color:#fff;text-decoration:underline;font-weight:600}'
    . '.wpcyt_back a:hover{text-decoration:none}'
    . '.wpcyt_back a:focus-visible{outline:2px solid #fff;outline-offset:2px;text-decoration:none;box-shadow:0 0 0 4px #1d2327}'
    . '@media print{.wpcyt_back{display:none}}';
}
