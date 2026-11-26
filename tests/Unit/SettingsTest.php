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

  public function testTheWayBackIsOnForANewSiteAndForOneSavedByVersion100(): void
  {
    Functions\when('get_option')->justReturn([]);
    $this->assertTrue(Settings::backLink());

    // 1.0.0 stored no such key.
    Functions\when('get_option')->justReturn(['allowed_themes' => ['a'], 'cookie_days' => 7]);
    $this->assertTrue(Settings::backLink());
    $this->assertSame(['allowed_themes' => ['a'], 'cookie_days' => 7, 'back_link' => true], Settings::get());
  }

  public function testTheWayBackCanBeSwitchedOff(): void
  {
    foreach ([false, 0, '0', 'off', ''] as $off) {
      Functions\when('get_option')->justReturn(['back_link' => $off]);
      $this->assertFalse(Settings::backLink(), var_export($off, true));
    }

    foreach ([true, 1, '1', 'on', 'garbage', ['x']] as $on) {
      Functions\when('get_option')->justReturn(['back_link' => $on]);
      $this->assertTrue(Settings::backLink(), var_export($on, true));
    }
  }

  public function testSanitizeReadsTheCheckboxAndDefaultsToOnWhenItIsMissing(): void
  {
    $this->assertFalse(Settings::sanitize(['back_link' => '0'], [])['back_link']);
    $this->assertTrue(Settings::sanitize(['back_link' => '1'], [])['back_link']);
    $this->assertTrue(Settings::sanitize(['cookie_days' => '9'], [])['back_link'], 'a form that does not send it leaves it on');
    $this->assertTrue(Settings::sanitize('nonsense', [])['back_link']);
  }
}
