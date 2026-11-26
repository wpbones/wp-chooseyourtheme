<?php

namespace WPChooseYourTheme\Support;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * The visitor's choice: read from a cookie, set by a link or a form, applied to the theme
 * WordPress loads.
 *
 * The cookie holds the theme's stylesheet slug (never its name), and nothing is applied unless that
 * slug passes {@see Themes::isSelectable()}: a forged cookie can only ever select a theme the
 * administrator allowed. The choice is applied on the public site only, never in wp-admin, the REST
 * API, Ajax, cron, XML-RPC, the login screen, the Customizer or a theme preview.
 */
class Visitor
{
  /** The cookie, and the query argument that sets it. */
  public const COOKIE = 'wpcyt_theme';
  public const PARAM = 'wpcyt_theme';

  /** Query arguments of the Customizer and of the theme preview: they pick a theme of their own. */
  private const PREVIEW_ARGS = ['customize_changeset_uuid', 'customize_theme', 'wp_customize', 'wp_theme_preview'];

  /**
   * @var array<int, array{slug: ?string, template: ?string, name: ?string, stylesheet_root: ?string, template_root: ?string}>
   *      The decision for this request, once taken, by blog: on a network the code can switch_to_blog(),
   *      and a theme validated for one site says nothing about another.
   */
  private static array $decisions = [];

  private static bool $deciding = false;

  /** While above zero the filters stand aside: the caller wants the site's own theme. */
  private static int $bypass = 0;

  /**
   * Whether this request is a page of the public site.
   */
  public static function isFrontEnd(): bool
  {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
      return false;
    }

    if ((defined('WP_CLI') && WP_CLI) || defined('XMLRPC_REQUEST') || defined('REST_REQUEST')) {
      return false;
    }

    if (in_array($GLOBALS['pagenow'] ?? '', ['wp-login.php', 'wp-cron.php', 'xmlrpc.php'], true)) {
      return false;
    }

    // REST_REQUEST is defined only once the REST server has started, which is after the theme is
    // loaded: the request has to be recognised by its address.
    if (isset($_GET['rest_route'])) {
      return false;
    }

    $path = (string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);

    if (str_contains(trailingslashit($path), '/' . trim(rest_get_url_prefix(), '/') . '/')) {
      return false;
    }

    foreach (self::PREVIEW_ARGS as $arg) {
      if (isset($_REQUEST[$arg])) {
        return false;
      }
    }

    if (function_exists('is_customize_preview') && is_customize_preview()) {
      return false;
    }

    return true;
  }

  /**
   * The stylesheet slug this visitor is shown, or null for the site's own theme.
   */
  public static function slug(): ?string
  {
    return self::decide()['slug'];
  }

  /**
   * The `template` filter: the parent theme of the visitor's theme.
   */
  public static function filterTemplate($template)
  {
    return self::decide()['template'] ?? $template;
  }

  /**
   * The `stylesheet` filter: the visitor's theme.
   */
  public static function filterStylesheet($stylesheet)
  {
    return self::decide()['slug'] ?? $stylesheet;
  }

  /**
   * `pre_option_stylesheet`, like the Customizer's theme preview: core reads some of what belongs to a
   * theme straight from the option (get_theme_mods() reads the theme mods of get_option('stylesheet')),
   * so without this the visitor would get the chosen theme with the active theme's logo, colours and
   * menus.
   */
  public static function preStylesheet($pre)
  {
    return self::decide()['slug'] ?? $pre;
  }

  public static function preTemplate($pre)
  {
    return self::decide()['template'] ?? $pre;
  }

  public static function preCurrentTheme($pre)
  {
    return self::decide()['name'] ?? $pre;
  }

  public static function preStylesheetRoot($pre)
  {
    return self::decide()['stylesheet_root'] ?? $pre;
  }

  public static function preTemplateRoot($pre)
  {
    return self::decide()['template_root'] ?? $pre;
  }

  /**
   * Run $callback with the choice switched off: what it reads is the site's own theme.
   *
   * @template T
   * @param callable(): T $callback
   * @return T
   */
  public static function withoutChoice(callable $callback)
  {
    self::$bypass++;

    try {
      return $callback();
    } finally {
      self::$bypass--;
    }
  }

  /**
   * Forget the decisions taken for this request.
   */
  public static function reset(): void
  {
    self::$decisions = [];
    self::$deciding = false;
    self::$bypass = 0;
  }

  /**
   * @return array{slug: ?string, template: ?string, name: ?string, stylesheet_root: ?string, template_root: ?string}
   */
  private static function decide(): array
  {
    $none = ['slug' => null, 'template' => null, 'name' => null, 'stylesheet_root' => null, 'template_root' => null];

    // The REST server defines this only after the theme is loaded, so isFrontEnd() cannot see it at
    // first; a filter asked again later can.
    if (self::$bypass > 0 || (defined('REST_REQUEST') && REST_REQUEST)) {
      return $none;
    }

    $blog = (int) get_current_blog_id();

    if (isset(self::$decisions[$blog])) {
      return self::$decisions[$blog];
    }

    // Deciding asks WordPress about themes, which asks for the active theme, which comes back here.
    if (self::$deciding) {
      return $none;
    }

    $slug = self::cookieValue();

    // The common case, no cookie, costs nothing: no theme is looked up.
    if ($slug === null) {
      return self::$decisions[$blog] = $none;
    }

    self::$deciding = true;

    try {
      $decision = $none;

      if (self::isFrontEnd() && $slug !== Themes::activeSlug() && Themes::isSelectable($slug)) {
        $theme = Themes::get($slug);

        if ($theme !== null) {
          $template = (string) $theme->get_template();
          $decision = [
            'slug' => $slug,
            'template' => $template,
            'name' => (string) $theme->get('Name'),
            'stylesheet_root' => (string) get_raw_theme_root($slug, true),
            'template_root' => (string) get_raw_theme_root($template, true),
          ];
        }
      }
    } finally {
      self::$deciding = false;
    }

    return self::$decisions[$blog] = $decision;
  }

  /**
   * The raw value of the cookie, or null when there is none.
   */
  private static function cookieValue(): ?string
  {
    $value = $_COOKIE[self::COOKIE] ?? null;

    if (!is_string($value) || $value === '') {
      return null;
    }

    return wp_unslash($value);
  }

  /**
   * `init`: a link or a form set the argument; remember the choice and go back to the same page
   * without it. Only the public site, only a GET.
   */
  public static function handleRequest(): void
  {
    if (!isset($_GET[self::PARAM]) || !in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
      return;
    }

    if (!static::isFrontEnd()) {
      return;
    }

    // An array or anything else is not a theme: nothing is stored, the argument is just dropped.
    $slug = is_string($_GET[self::PARAM]) ? wp_unslash($_GET[self::PARAM]) : null;

    if ($slug === '' || ($slug !== null && $slug === Themes::activeSlug())) {
      // Back to the site's own theme: the cookie goes, and a page cache may serve the visitor again.
      static::sendCookie('', 1);
    } elseif ($slug !== null && Themes::isSelectable($slug)) {
      static::sendCookie($slug, time() + Settings::cookieDays() * DAY_IN_SECONDS);
    }

    static::redirect(static::cleanLocation());
  }

  /**
   * `send_headers`: a page that depends on a cookie must not be kept for anyone else.
   */
  public static function sendHeaders(): void
  {
    if (isset($_COOKIE[self::COOKIE]) && static::isFrontEnd()) {
      nocache_headers();
      header('Vary: Cookie', false);

      // Cache plugins and hosts that ignore the headers above honour this constant (WP Super Cache,
      // W3 Total Cache, WP Rocket, LiteSpeed Cache and others): a page in another theme is not kept.
      if (static::slug() !== null && !defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
      }
    }
  }

  /**
   * Where to go once the choice is stored: the page the visitor was on, minus the argument.
   */
  public static function cleanLocation(): string
  {
    return Url::relative(Url::without((string) wp_unslash($_SERVER['REQUEST_URI'] ?? '/'), self::PARAM));
  }

  /**
   * The options of the cookie.
   *
   * SameSite=Lax: the choice is carried by a visitor's own click, never by another site's request.
   * HttpOnly: no script needs it, the server reads it. Secure on HTTPS.
   *
   * @return array{expires: int, path: string, domain: string, secure: bool, httponly: bool, samesite: string}
   */
  public static function cookieOptions(int $expires, bool $secure, string $path, string $domain): array
  {
    return [
      'expires' => $expires,
      'path' => $path,
      'domain' => $domain,
      'secure' => $secure,
      'httponly' => true,
      'samesite' => 'Lax',
    ];
  }

  /**
   * @param int $expires A Unix time; 1 deletes the cookie.
   */
  protected static function sendCookie(string $value, int $expires): void
  {
    if (headers_sent()) {
      return;
    }

    setcookie(
      self::COOKIE,
      $value,
      self::cookieOptions(
        $expires,
        is_ssl(),
        defined('COOKIEPATH') ? (string) COOKIEPATH : '/',
        defined('COOKIE_DOMAIN') && COOKIE_DOMAIN ? (string) COOKIE_DOMAIN : ''
      )
    );
  }

  protected static function redirect(string $location): void
  {
    nocache_headers();
    wp_safe_redirect($location, 302, 'WP Choose Your Theme');
    static::finish();
  }

  /**
   * The end of a redirect. A method of its own so that a test can step over it.
   */
  protected static function finish(): void
  {
    exit();
  }
}
