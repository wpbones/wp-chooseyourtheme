<?php

declare(strict_types=1);

namespace WPChooseYourTheme\Tests\Unit;

use Brain\Monkey\Functions;
use WPChooseYourTheme\Support\BackLink;
use WPChooseYourTheme\Support\Renderer;
use WPChooseYourTheme\Support\Settings;
use WPChooseYourTheme\Support\Visitor;
use WPChooseYourTheme\Tests\Support\FakeTheme;
use WPChooseYourTheme\Tests\Support\MonkeyTestCase;
use WPChooseYourTheme\Tests\Support\ThemeWorld;

final class BackLinkTest extends MonkeyTestCase
{
  use ThemeWorld;

  protected function setUp(): void
  {
    parent::setUp();

    Functions\when('is_admin')->justReturn(false);
    Functions\when('wp_doing_ajax')->justReturn(false);
    Functions\when('wp_doing_cron')->justReturn(false);
    Functions\when('wp_enqueue_script')->justReturn(true);
    Functions\when('plugins_url')->justReturn('https://example.test/p.js');
    Functions\when('selected')->alias(fn ($a, $b, $echo = true) => '');
    Functions\when('apply_filters')->alias(fn ($tag, $value) => $value);
    Functions\when('esc_attr__')->alias(fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES));
  }

  /**
   * The stuck case: the site's own theme is "Mom & Pop", the visitor chose "Kid <b>", and what they
   * see is the page of the theme they chose.
   *
   * @param array<string, mixed> $settings
   */
  private function stuck(array $settings = []): void
  {
    $this->site(
      'mom',
      [new FakeTheme('mom', 'Mom & Pop'), new FakeTheme('kid', 'Kid <b>')],
      $settings + ['allowed_themes' => ['kid']]
    );
    $_COOKIE[Visitor::COOKIE] = 'kid';
    $_SERVER['REQUEST_URI'] = '/blog/?s=cat&wpcyt_theme=kid';
  }

  public function testItIsShownToAVisitorWhoChoseAnotherThemeOnAPageWithNoSwitcher(): void
  {
    $this->stuck();

    $html = BackLink::html();

    $this->assertStringContainsString('<aside class="wpcyt_back" aria-label="Theme choice">', $html, 'a landmark with a name');
    $this->assertStringContainsString('You are viewing this site with Kid &lt;b&gt;.', $html);
    $this->assertStringContainsString('<a href="/blog/?s=cat&amp;wpcyt_theme=mom" rel="nofollow">Back to Mom &amp; Pop</a>', $html, 'the same page, the choice link to the active theme');
    $this->assertStringContainsString('<style id="wpcyt-back-style">', $html);
    $this->assertStringContainsString('@media print{.wpcyt_back{display:none}}', $html);
    $this->assertStringContainsString('inset-block-end:0', $html, 'logical properties');
    $this->assertStringContainsString('position:sticky', $html, 'after the last line, never over it');
    $this->assertStringNotContainsString('position:fixed', $html);
    $this->assertStringNotContainsString('transition', $html);
    $this->assertStringNotContainsString('animation', $html);
    $this->assertStringNotContainsString('<b>', $html, 'names are escaped');
  }

  public function testItPrintsNothingAtAllWhenThereIsNoReason(): void
  {
    $this->site('mom', [new FakeTheme('mom'), new FakeTheme('kid')], ['allowed_themes' => ['kid']]);

    $this->assertSame('', BackLink::html(), 'no cookie');
  }

  public function testItIsNotShownWhenASwitcherWasPrintedOnThePage(): void
  {
    $this->stuck();
    $this->assertNotSame('', Renderer::render('list'), 'the switcher itself prints');

    $this->assertTrue(Renderer::printed());
    $this->assertSame('', BackLink::html());
  }

  public function testItIsShownWhenTheSwitcherHadNothingToShow(): void
  {
    // Nothing to choose from is not a switcher on the page; the visitor cannot be on another theme then.
    $this->site('mom', [new FakeTheme('mom'), new FakeTheme('kid')], ['allowed_themes' => []]);

    $this->assertSame('', Renderer::render('list'));
    $this->assertFalse(Renderer::printed());
  }

  public function testItIsNotShownToAVisitorOnTheSitesOwnTheme(): void
  {
    $this->stuck();
    $_COOKIE[Visitor::COOKIE] = 'mom';

    $this->assertSame('', BackLink::html());
  }

  public function testItIsNotShownForAForgedOrRefusedChoice(): void
  {
    $this->stuck(['allowed_themes' => []]);

    $this->assertSame('', BackLink::html(), 'the visitor is shown the active theme, so there is nothing to go back from');
  }

  public function testItIsNotShownWhenTheSettingIsOff(): void
  {
    $this->stuck(['back_link' => false]);

    $this->assertFalse(Settings::backLink());
    $this->assertSame('', BackLink::html());
  }

  public function testTheFilterCanSayNo(): void
  {
    $this->stuck();
    Functions\when('apply_filters')->alias(fn ($tag, $value) => $tag === 'wp_chooseyourtheme_show_back_link' ? false : $value);

    $this->assertSame('', BackLink::html());
  }

  public function testTheFilterIsGivenTheTwoThemes(): void
  {
    $this->stuck();
    Functions\when('apply_filters')->alias(function ($tag, $value, ...$args) {
      if ($tag === 'wp_chooseyourtheme_show_back_link') {
        $this->assertTrue($value);
        $this->assertSame(['kid', 'mom'], [$args[0]->slug, $args[1]->slug]);
      }

      return $value;
    });

    $this->assertNotSame('', BackLink::html());
  }

  public function testItIsNotShownOutsideThePublicSite(): void
  {
    $this->stuck();
    Functions\when('is_admin')->justReturn(true);

    $this->assertSame('', BackLink::html());
  }

  public function testPrintEchoesTheBar(): void
  {
    $this->stuck();

    ob_start();
    BackLink::print();

    $this->assertStringContainsString('class="wpcyt_back"', (string) ob_get_clean());
  }
}
