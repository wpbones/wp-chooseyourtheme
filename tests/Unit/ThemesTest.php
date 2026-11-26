<?php

declare(strict_types=1);

namespace WPChooseYourTheme\Tests\Unit;

use Brain\Monkey\Filters;
use WPChooseYourTheme\Support\Themes;
use WPChooseYourTheme\Tests\Support\FakeTheme;
use WPChooseYourTheme\Tests\Support\MonkeyTestCase;
use WPChooseYourTheme\Tests\Support\ThemeWorld;

final class ThemesTest extends MonkeyTestCase
{
  use ThemeWorld;

  public function testTheActiveThemeIsAlwaysSelectable(): void
  {
    $this->site('b', [new FakeTheme('b')], ['allowed_themes' => []]);

    $this->assertTrue(Themes::isSelectable('b'));
  }

  public function testAnInstalledThemeThatIsNotAllowedIsNot(): void
  {
    $this->site('b', [new FakeTheme('a'), new FakeTheme('b')], ['allowed_themes' => []]);

    $this->assertFalse(Themes::isSelectable('a'));
  }

  public function testAnAllowedThemeIs(): void
  {
    $this->site('b', [new FakeTheme('a'), new FakeTheme('b')], ['allowed_themes' => ['a']]);

    $this->assertTrue(Themes::isSelectable('a'));
  }

  public function testAnAllowedThemeThatIsNotInstalledAnymoreIsNot(): void
  {
    $this->site('b', [new FakeTheme('b')], ['allowed_themes' => ['gone']]);

    $this->assertFalse(Themes::isSelectable('gone'));
  }

  public function testAThemeWithErrorsIsNot(): void
  {
    $this->site('b', [new FakeTheme('a', broken: true), new FakeTheme('b')], ['allowed_themes' => ['a']]);

    $this->assertFalse(Themes::isSelectable('a'));
  }

  public function testAThemeTheSiteMayNotUseIsNot(): void
  {
    $this->site('b', [new FakeTheme('a', allowed: false), new FakeTheme('b')], ['allowed_themes' => ['a']]);

    $this->assertFalse(Themes::isSelectable('a'));
  }

  public function testABlockThemeIsNotOfferedOnAClassicSite(): void
  {
    $this->site('classic', [new FakeTheme('classic'), new FakeTheme('block', block: true)], ['allowed_themes' => ['block']]);

    $this->assertFalse(Themes::isSelectable('block'));
    $this->assertSame(['classic'], array_keys(Themes::selectable()));
  }

  public function testAClassicThemeIsNotOfferedOnABlockSite(): void
  {
    $this->site('block', [new FakeTheme('classic'), new FakeTheme('block', block: true)], ['allowed_themes' => ['classic']]);

    $this->assertFalse(Themes::isSelectable('classic'));
  }

  public function testTheKindsMixWhenTheFilterSaysSo(): void
  {
    $this->site('classic', [new FakeTheme('classic'), new FakeTheme('block', block: true)], ['allowed_themes' => ['block']]);
    Filters\expectApplied('wp_chooseyourtheme_allow_mixed_kinds')->andReturn(true);

    $this->assertTrue(Themes::isSelectable('block'));
  }

  public function testTheOfferIsTheActiveThemePlusTheAllowedOnesInNameOrder(): void
  {
    $this->site(
      'mid',
      [new FakeTheme('mid', 'Mid'), new FakeTheme('zed', 'Zed'), new FakeTheme('alpha', 'Alpha'), new FakeTheme('hidden', 'Hidden')],
      ['allowed_themes' => ['zed', 'alpha']]
    );

    $this->assertSame(['alpha', 'mid', 'zed'], array_keys(Themes::selectable()));
  }

  public function testABrokenActiveThemeStaysInTheOffer(): void
  {
    $this->site('b', [new FakeTheme('a'), new FakeTheme('b', broken: true)], ['allowed_themes' => ['a']]);

    $this->assertContains('b', array_keys(Themes::selectable()));
  }

  public function testAnEmptySlugIsNeverSelectable(): void
  {
    $this->site('b', [new FakeTheme('b')]);

    $this->assertFalse(Themes::isSelectable(''));
  }
}
