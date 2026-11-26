<?php

declare(strict_types=1);

namespace WPChooseYourTheme\Tests\Unit;

use Brain\Monkey\Functions;
use WPChooseYourTheme\Support\Settings;
use WPChooseYourTheme\Tests\Support\MonkeyTestCase;

final class SettingsTest extends MonkeyTestCase
{
  public function testANewSiteAllowsNoThemeButTheActiveOne(): void
  {
    Functions\when('get_option')->justReturn([]);

    $this->assertSame([], Settings::allowedThemes());
    $this->assertSame(30, Settings::cookieDays());
  }

  public function testACorruptedRowFallsBackToTheDefaults(): void
  {
    Functions\when('get_option')->justReturn('garbage');

    $this->assertSame(Settings::DEFAULTS, Settings::get());
  }

  public function testSanitizeKeepsOnlyInstalledThemes(): void
  {
    $clean = Settings::sanitize(
      ['allowed_themes' => ['twentytwentyfive', 'no-such-theme', ['array'], 7, 'twentytwentyfive'], 'cookie_days' => '14'],
      ['twentytwentyfive', 'twentytwentyfour']
    );

    $this->assertSame(['twentytwentyfive'], $clean['allowed_themes']);
    $this->assertSame(14, $clean['cookie_days']);
  }

  public function testSanitizeClampsTheLifetime(): void
  {
    $this->assertSame(365, Settings::sanitize(['cookie_days' => '99999'], [])['cookie_days']);
    $this->assertSame(1, Settings::sanitize(['cookie_days' => '0'], [])['cookie_days']);
    $this->assertSame(1, Settings::sanitize(['cookie_days' => '-5'], [])['cookie_days']);
    $this->assertSame(30, Settings::sanitize(['cookie_days' => 'abc'], [])['cookie_days']);
  }

  public function testSanitizeSurvivesAnythingThePostCanBe(): void
  {
    $this->assertSame(Settings::DEFAULTS, Settings::sanitize('not an array', ['a']));
    $this->assertSame(Settings::DEFAULTS, Settings::sanitize(['allowed_themes' => 'a'], ['a']));
  }
}
