<?php

namespace WPChooseYourTheme\Support;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * The few URL manipulations the switcher needs, on the request URI as the server received it.
 */
final class Url
{
  /**
   * The request URI as a safe, site-relative reference: one leading slash, and no slash or
   * backslash after it, so that "//example.com" and "/\example.com" cannot become another host.
   */
  public static function relative(string $uri): string
  {
    $uri = str_replace(["\r", "\n", "\0"], '', $uri);

    return '/' . ltrim($uri, '/\\');
  }

  /**
   * The URI without the query argument $name, however PHP would read it: "name", "name[]" or "na.me".
   */
  public static function without(string $uri, string $name): string
  {
    [$path, $query] = array_pad(explode('?', $uri, 2), 2, '');

    $kept = [];
    foreach (explode('&', $query) as $pair) {
      if ($pair === '') {
        continue;
      }

      // PHP names the argument the way it will fill $_GET: decoded, up to the first "[", with the
      // spaces and dots it turns into underscores. "wpcyt.theme=x" is $_GET['wpcyt_theme'], and a
      // redirect that kept it would loop.
      $key = urldecode(explode('=', $pair, 2)[0]);
      $base = str_replace(['.', ' '], '_', ltrim(explode('[', $key, 2)[0]));

      if ($base === $name) {
        continue;
      }

      $kept[] = $pair;
    }

    return $kept ? $path . '?' . implode('&', $kept) : $path;
  }

  /**
   * The URI with the query argument $name set to $value, replacing any earlier one.
   */
  public static function with(string $uri, string $name, string $value): string
  {
    $uri = self::without($uri, $name);

    return $uri . (str_contains($uri, '?') ? '&' : '?') . rawurlencode($name) . '=' . rawurlencode($value);
  }

  /**
   * The query arguments of a URI, minus $name, as [name, value] pairs ready for hidden form fields:
   * a GET form replaces the query string of its action, so the page's own arguments (a search, a
   * page number) must travel in the form.
   *
   * @return array<int, array{0: string, 1: string}>
   */
  public static function fields(string $uri, string $name): array
  {
    $query = explode('?', self::without($uri, $name), 2)[1] ?? '';
    parse_str($query, $args);

    $fields = [];
    self::flatten($args, '', $fields);

    return $fields;
  }

  /**
   * @param array<int|string, mixed>                $args
   * @param array<int, array{0: string, 1: string}> $fields
   */
  private static function flatten(array $args, string $prefix, array &$fields): void
  {
    foreach ($args as $key => $value) {
      $name = $prefix === '' ? (string) $key : $prefix . '[' . $key . ']';

      if (is_array($value)) {
        self::flatten($value, $name, $fields);
      } else {
        $fields[] = [$name, (string) $value];
      }
    }
  }
}
