<?php

declare(strict_types=1);

namespace WPChooseYourTheme\Tests\Unit;

use Brain\Monkey\Functions;
use WPChooseYourTheme\Tests\Support\MonkeyTestCase;

/**
 * uninstall.php: every option goes, on every site, and only when WordPress is deleting the plugin.
 */
final class UninstallTest extends MonkeyTestCase
{
  private const FILE = __DIR__ . '/../../uninstall.php';

  /** @var string[] */
  private array $deleted = [];

  protected function setUp(): void
  {
    parent::setUp();

    $this->deleted = [];
    Functions\when('delete_option')->alias(function (string $name) {
      $this->deleted[] = $name;

      return true;
    });
    if (!defined('WP_UNINSTALL_PLUGIN')) {
      define('WP_UNINSTALL_PLUGIN', 'wp-chooseyourtheme/wp-chooseyourtheme.php');
    }
  }

  public function testItDeletesEveryOptionThePluginStores(): void
  {
    Functions\when('is_multisite')->justReturn(false);

    include self::FILE;

    $this->assertEqualsCanonicalizing([
      'wp_chooseyourtheme_settings',
      'wp-chooseyourtheme',
      'widget_wp_chooseyourtheme',
      'wp_choose_your_theme_slug',
      'wp_choose_your_theme_slug_db_version',
      'wp_choose_your_theme_slug_migrations',
      'wp_choose_your_theme_slug_migrations_failure',
      'wp_choose_your_theme_slug_migrations.lock',
    ], $this->deleted);
  }

  public function testItCoversEverySiteOfANetwork(): void
  {
    $switched = [];
    Functions\when('is_multisite')->justReturn(true);
    Functions\when('get_sites')->justReturn([1, 2, 3]);
    Functions\when('switch_to_blog')->alias(function (int $id) use (&$switched) {
      $switched[] = $id;
    });
    Functions\when('restore_current_blog')->justReturn(true);

    include self::FILE;

    $this->assertSame([1, 2, 3], $switched);
    $this->assertCount(3 * 8, $this->deleted);
  }
}
