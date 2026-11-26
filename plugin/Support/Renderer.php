<?php

namespace WPChooseYourTheme\Support;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * The three ways to show the choice, shared by the template tag, the shortcode and the widget:
 *
 * - menu: a form with a dropdown and a button; a few lines of script submit it on change.
 * - list: a list of links.
 * - roll: one link to the next theme, in the order of the names, round to the first.
 *
 * Every choice is a plain link or a GET form to `?wpcyt_theme=<slug>`, so it works without
 * JavaScript, and the page answers with a redirect to the same address without the argument.
 */
final class Renderer
{
  public const MODES = ['menu', 'list', 'roll'];
  public const DEFAULT_MODE = 'menu';

  private static int $instances = 0;

  /** Whether a switcher was printed on this page: the way-back bar is for pages that have none. */
  private static bool $printed = false;

  /**
   * @param string $mode One of {@see self::MODES}; anything else is the menu.
   */
  public static function render(string $mode = self::DEFAULT_MODE): string
  {
    $themes = Themes::selectable();

    // One theme is nothing to choose from.
    if (count($themes) < 2) {
      return '';
    }

    $uri = self::uri();
    $current = (string) get_stylesheet();

    self::$printed = true;

    return match ($mode) {
      'list' => self::list($themes, $current, $uri),
      'roll' => self::roll($themes, $current, $uri),
      default => self::menu($themes, $current, $uri),
    };
  }

  /**
   * The address the switcher's links return to.
   *
   * The request's own, except in a REST request, where it is the endpoint's: the editor's preview
   * (ServerSideRender) has no page, so it points home; another REST request (`content.rendered` of a
   * post for a headless front end) points at the post being rendered.
   */
  private static function uri(): string
  {
    if (!wp_is_serving_rest_request()) {
      return Url::relative((string) wp_unslash($_SERVER['REQUEST_URI'] ?? '/'));
    }

    $permalink = self::isEditorPreview() ? false : get_permalink();

    if (!is_string($permalink) || $permalink === '') {
      return '/';
    }

    // The path and query of the address, as a request URI would carry them.
    $parts = (array) wp_parse_url($permalink);

    return Url::relative(($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : ''));
  }

  /**
   * Whether this is the block editor's preview request, and not any REST request: ServerSideRender
   * asks the `block-renderer` route. Anything else that renders the block over REST (a post's
   * `content.rendered`) is front-end content and gets the front end's markup.
   */
  public static function isEditorPreview(): bool
  {
    if (!wp_is_serving_rest_request()) {
      return false;
    }

    $route = $GLOBALS['wp']->query_vars['rest_route'] ?? ($_GET['rest_route'] ?? '');

    return self::isEditorRoute(is_string($route) ? $route : '');
  }

  /**
   * @param string $route A REST route, as `rest_route` holds it: `/wp/v2/block-renderer/<block>`.
   */
  public static function isEditorRoute(string $route): bool
  {
    return str_starts_with(ltrim($route, '/'), 'wp/v2/block-renderer/');
  }

  /**
   * Whether a switcher, by any way in, was printed on this page so far.
   */
  public static function printed(): bool
  {
    return self::$printed;
  }

  /**
   * Forget what was printed; for tests.
   */
  public static function reset(): void
  {
    self::$printed = false;
  }

  public static function normalizeMode(mixed $mode): string
  {
    $mode = is_string($mode) ? strtolower(trim($mode)) : '';

    return in_array($mode, self::MODES, true) ? $mode : self::DEFAULT_MODE;
  }

  /**
   * @param array<string, object> $themes
   */
  private static function menu(array $themes, string $current, string $uri): string
  {
    $id = 'wpcyt-' . ++self::$instances;

    Assets::enqueue();

    $html = '<form class="wpcyt_form" method="get" action="' . esc_url(explode('?', $uri, 2)[0]) . '">';

    foreach (Url::fields($uri, Visitor::PARAM) as [$name, $value]) {
      $html .= '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" />';
    }

    $html .= '<label class="screen-reader-text" for="' . esc_attr($id) . '">' . esc_html__('Theme', 'wp-chooseyourtheme') . '</label>';
    $html .= '<select class="wpcyt_combo" id="' . esc_attr($id) . '" name="' . esc_attr(Visitor::PARAM) . '">';

    foreach ($themes as $slug => $theme) {
      $slug = (string) $slug;
      $html .= '<option value="' . esc_attr($slug) . '"' . selected($slug, $current, false) . '>' . esc_html((string) $theme->get('Name')) . '</option>';
    }

    $html .= '</select> ';
    $html .= '<button type="submit" class="wpcyt_submit">' . esc_html__('Change theme', 'wp-chooseyourtheme') . '</button>';

    return $html . '</form>';
  }

  /**
   * @param array<string, object> $themes
   */
  private static function list(array $themes, string $current, string $uri): string
  {
    $html = '<ul class="wpcyt_list">';

    foreach ($themes as $slug => $theme) {
      $slug = (string) $slug;
      $name = (string) $theme->get('Name');
      $isCurrent = $slug === $current;
      $class = self::cssClass($name) . ($isCurrent ? ' selected' : '');

      $html .= '<li class="' . esc_attr($class) . '"><a href="' . esc_url(Url::with($uri, Visitor::PARAM, $slug)) . '" rel="nofollow"'
        . ($isCurrent ? ' aria-current="true"' : '') . '><span>' . esc_html($name) . '</span></a></li>';
    }

    return $html . '</ul>';
  }

  /**
   * @param array<string, object> $themes
   */
  private static function roll(array $themes, string $current, string $uri): string
  {
    // PHP turns a numeric folder name such as "2024" into an int key.
    $slugs = array_map('strval', array_keys($themes));
    $position = array_search($current, $slugs, true);
    $next = $slugs[($position === false ? 0 : $position + 1) % count($slugs)];
    $name = (string) $themes[$next]->get('Name');

    return '<div class="wpcyt_roll"><a class="' . esc_attr(self::cssClass($name)) . '" href="' . esc_url(Url::with($uri, Visitor::PARAM, $next))
      . '" rel="nofollow"><span>' . esc_html($name) . '</span></a></div>';
  }

  /**
   * The class 0.x gave each theme: its name, lower case, letters and digits, dashes between words.
   */
  public static function cssClass(string $name): string
  {
    $slug = preg_replace('/[^a-zA-Z0-9 ]/', '', $name) ?? '';

    return strtolower(str_replace(' ', '-', $slug));
  }
}
