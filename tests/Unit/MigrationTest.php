<?php

declare(strict_types=1);

namespace WPChooseYourTheme\Tests\Unit;

use Brain\Monkey\Functions;
use WPChooseYourTheme\Support\Migration;
use WPChooseYourTheme\Support\Settings;
use WPChooseYourTheme\Tests\Support\FakeTheme;
use WPChooseYourTheme\Tests\Support\MonkeyTestCase;

final class MigrationTest extends MonkeyTestCase
{
  /** @return array<string, FakeTheme> */
  private function installed(): array
  {
    return [
      'twentytwentyfive' => new FakeTheme('twentytwentyfive', 'Twenty Twenty-Five'),
      'twentytwentyfour' => new FakeTheme('twentytwentyfour', 'Twenty Twenty-Four'),
      'twentytwentythree' => new FakeTheme('twentytwentythree', 'Twenty Twenty-Three'),
    ];
  }

  public function testThemesThatWereNotHiddenBecomeAllowed(): void
  {
    $allowed = Migration::allowedFrom(['hidden_themes' => ['Twenty Twenty-Four']], $this->installed());

    $this->assertSame(['twentytwentyfive', 'twentytwentythree'], $allowed);
  }

  public function testAnEmptyHiddenListAllowsEveryTheme(): void
  {
    $this->assertSame(
      ['twentytwentyfive', 'twentytwentyfour', 'twentytwentythree'],
      Migration::allowedFrom(['hidden_themes' => []], $this->installed())
    );
  }

  public function testAHiddenThemeNamedByDirectoryIsHiddenToo(): void
  {
    $this->assertSame(
      ['twentytwentyfive', 'twentytwentythree'],
      Migration::allowedFrom(['hidden_themes' => ['twentytwentyfour']], $this->installed())
    );
  }

  public function testAMalformedLegacyRowHidesNothing(): void
  {
    $this->assertCount(3, Migration::allowedFrom(['hidden_themes' => 'x'], $this->installed()));
    $this->assertCount(3, Migration::allowedFrom([], $this->installed()));
  }

  public function testTheFirstLoadAfterAnUpdateStoresTheNewSettings(): void
  {
    Functions\when('get_option')->alias(fn ($name, $default = false) => match ($name) {
      Settings::LEGACY_OPTION => ['hidden_themes' => ['Twenty Twenty-Four']],
      default => $default,
    });
    Functions\when('wp_get_themes')->justReturn($this->installed());
    Functions\expect('add_option')->once()->with(Settings::OPTION, [
      'allowed_themes' => ['twentytwentyfive', 'twentytwentythree'],
      'cookie_days' => 30,
      'back_link' => true,
    ]);

    Migration::run();
  }

  public function testANewInstallationStoresTheDefaults(): void
  {
    Functions\when('get_option')->alias(fn ($name, $default = false) => $default);
    Functions\expect('add_option')->once()->with(Settings::OPTION, Settings::DEFAULTS);

    Migration::run();
  }

  public function testSettingsAlreadyStoredAreNeverOverwritten(): void
  {
    Functions\when('get_option')->alias(fn ($name, $default = false) => match ($name) {
      Settings::OPTION => ['allowed_themes' => []],
      Settings::LEGACY_OPTION => ['hidden_themes' => []],
      default => $default,
    });
    Functions\expect('add_option')->never();
    Functions\expect('update_option')->never();

    Migration::run();
  }
}
