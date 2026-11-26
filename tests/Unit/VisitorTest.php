<?php

declare(strict_types=1);

namespace WPChooseYourTheme\Tests\Unit;

use Brain\Monkey\Functions;
use WPChooseYourTheme\Support\Visitor;
use WPChooseYourTheme\Tests\Support\FakeTheme;
use WPChooseYourTheme\Tests\Support\StepOver;
use WPChooseYourTheme\Tests\Support\MonkeyTestCase;
use WPChooseYourTheme\Tests\Support\ThemeWorld;

final class VisitorTest extends MonkeyTestCase
{
  use ThemeWorld;

  protected function setUp(): void
  {
    parent::setUp();

    Functions\when('is_admin')->justReturn(false);
    Functions\when('wp_doing_ajax')->justReturn(false);
    Functions\when('wp_doing_cron')->justReturn(false);
  }

  /** A classic site whose child theme "kid" has the parent "mom". */
  private function blog(array $settings = ['allowed_themes' => ['kid', 'other']]): void
  {
    $this->site(
      'mom',
      [new FakeTheme('mom'), new FakeTheme('kid', template: 'mom'), new FakeTheme('other'), new FakeTheme('hidden')],
      $settings
    );
  }

  // ---- what the visitor is shown --------------------------------------------------------------

  public function testWithoutACookieNothingChangesAndNoThemeIsLookedUp(): void
  {
    Functions\expect('wp_get_theme')->never();
    Functions\expect('wp_get_themes')->never();

    $this->assertSame('mom', Visitor::filterStylesheet('mom'));
    $this->assertSame('mom', Visitor::filterTemplate('mom'));
  }

  public function testAnAllowedChoiceSwapsStylesheetAndTemplate(): void
  {
    $this->blog();
    $_COOKIE[Visitor::COOKIE] = 'other';

    $this->assertSame('other', Visitor::filterStylesheet('mom'));
    $this->assertSame('other', Visitor::filterTemplate('mom'));
  }

  public function testAChildThemeSwapsTheStylesheetForItselfAndTheTemplateForItsParent(): void
  {
    $this->site('x', [new FakeTheme('x'), new FakeTheme('kid', template: 'mom'), new FakeTheme('mom')], ['allowed_themes' => ['kid']]);
    $_COOKIE[Visitor::COOKIE] = 'kid';

    $this->assertSame('kid', Visitor::filterStylesheet('x'));
    $this->assertSame('mom', Visitor::filterTemplate('x'));
  }

  public function testAThemeThatIsNotAllowedIsRefused(): void
  {
    $this->blog();
    $_COOKIE[Visitor::COOKIE] = 'hidden';

    $this->assertSame('mom', Visitor::filterStylesheet('mom'));
    $this->assertSame('mom', Visitor::filterTemplate('mom'));
  }

  public function testAThemeThatIsNotInstalledIsRefused(): void
  {
    $this->blog(['allowed_themes' => ['ghost']]);
    $_COOKIE[Visitor::COOKIE] = 'ghost';

    $this->assertSame('mom', Visitor::filterStylesheet('mom'));
  }

  public function testTheOldNameBasedCookieChoosesNothing(): void
  {
    $this->blog();
    $_COOKIE['wpcyt_name'] = 'Other';

    $this->assertSame('mom', Visitor::filterStylesheet('mom'));
  }

  public function testAnArrayCookieChoosesNothing(): void
  {
    $this->blog();
    $_COOKIE[Visitor::COOKIE] = ['other'];

    $this->assertSame('mom', Visitor::filterStylesheet('mom'));
  }

  public function testTheDecisionIsTakenOncePerRequest(): void
  {
    $this->blog();
    $_COOKIE[Visitor::COOKIE] = 'other';

    $this->assertSame('other', Visitor::filterStylesheet('mom'));

    // WordPress asks again and again; whatever happens to the cookie meanwhile, the page is one theme.
    $_COOKIE[Visitor::COOKIE] = 'kid';
    $this->assertSame('other', Visitor::filterStylesheet('mom'));
    $this->assertSame('other', Visitor::slug());
  }

  public function testAskingForTheThemeWhileDecidingDoesNotRecurse(): void
  {
    $this->blog();
    $_COOKIE[Visitor::COOKIE] = 'other';

    // wp_get_themes() asks for get_stylesheet(), which is this filter again.
    Functions\when('wp_get_theme')->alias(function ($slug = '') {
      $this->assertSame('inner', Visitor::filterStylesheet('inner'), 'the nested call gets what it passed');

      return new FakeTheme((string) $slug);
    });
    Functions\when('get_option')->alias(fn ($name, $default = false) => $name === 'stylesheet' ? 'mom' : ['allowed_themes' => ['other']]);

    $this->assertSame('other', Visitor::filterStylesheet('mom'));
  }

  // ---- where it is applied --------------------------------------------------------------------

  public function testItIsAFrontEndRequest(): void
  {
    $_SERVER['REQUEST_URI'] = '/2026/11/a-post/';

    $this->assertTrue(Visitor::isFrontEnd());
  }

  public function testNotInTheAdmin(): void
  {
    Functions\when('is_admin')->justReturn(true);

    $this->assertFalse(Visitor::isFrontEnd());
  }

  public function testNotInAjaxOrCron(): void
  {
    Functions\when('wp_doing_ajax')->justReturn(true);
    $this->assertFalse(Visitor::isFrontEnd());

    Functions\when('wp_doing_ajax')->justReturn(false);
    Functions\when('wp_doing_cron')->justReturn(true);
    $this->assertFalse(Visitor::isFrontEnd());
  }

  public function testNotOnTheLoginScreen(): void
  {
    $GLOBALS['pagenow'] = 'wp-login.php';

    $this->assertFalse(Visitor::isFrontEnd());
  }

  public function testNotInTheRestApi(): void
  {
    $_SERVER['REQUEST_URI'] = '/wp-json/wp/v2/posts?per_page=1';
    $this->assertFalse(Visitor::isFrontEnd());

    $_SERVER['REQUEST_URI'] = '/blog/wp-json';
    $this->assertFalse(Visitor::isFrontEnd());

    $_SERVER['REQUEST_URI'] = '/?rest_route=/wp/v2/posts';
    $_GET['rest_route'] = '/wp/v2/posts';
    $this->assertFalse(Visitor::isFrontEnd());
  }

  public function testNotInTheCustomizerOrAThemePreview(): void
  {
    foreach (['customize_changeset_uuid', 'customize_theme', 'wp_customize', 'wp_theme_preview'] as $arg) {
      $_REQUEST = [$arg => 'x'];
      $this->assertFalse(Visitor::isFrontEnd(), $arg);
    }
  }

  public function testTheCookieIsNotAppliedOutsideTheFrontEnd(): void
  {
    $this->blog();
    Functions\when('is_admin')->justReturn(true);
    $_COOKIE[Visitor::COOKIE] = 'other';

    $this->assertSame('mom', Visitor::filterStylesheet('mom'));
  }

  // ---- the cookie -----------------------------------------------------------------------------

  public function testTheCookieIsSameSiteLaxHttpOnlyAndSecureOnHttps(): void
  {
    $options = Visitor::cookieOptions(1_800_000_000, true, '/blog/', 'example.com');

    $this->assertSame('Lax', $options['samesite']);
    $this->assertTrue($options['httponly']);
    $this->assertTrue($options['secure']);
    $this->assertSame(1_800_000_000, $options['expires']);
    $this->assertSame('/blog/', $options['path']);
    $this->assertFalse(Visitor::cookieOptions(1, false, '/', '')['secure']);
  }

  // ---- the request that sets it ---------------------------------------------------------------

  private function handle(array $get, string $uri = '/page/?a=1'): ?StepOver
  {
    $_GET = $get;
    $_SERVER['REQUEST_URI'] = $uri . ($get ? (str_contains($uri, '?') ? '&' : '?') . http_build_query($get) : '');
    StepOver::$cookies = [];
    StepOver::$redirects = [];

    StepOver::handleRequest();

    return StepOver::$redirects ? new StepOver() : null;
  }

  public function testAnAllowedChoiceIsStoredAndTheVisitorGoesBackWithoutTheArgument(): void
  {
    $this->blog();
    Functions\when('wp_parse_url')->alias(fn ($url, $component = -1) => parse_url((string) $url, $component));

    $this->handle([Visitor::PARAM => 'other']);

    $this->assertSame([['other', time() + 30 * DAY_IN_SECONDS]], array_map(fn ($c) => [$c[0], $c[1]], StepOver::$cookies));
    $this->assertSame(['/page/?a=1'], StepOver::$redirects);
  }

  public function testTheCookieLifetimeIsTheConfiguredOne(): void
  {
    $this->blog(['allowed_themes' => ['other'], 'cookie_days' => 7]);

    $this->handle([Visitor::PARAM => 'other']);

    $this->assertEqualsWithDelta(time() + 7 * DAY_IN_SECONDS, StepOver::$cookies[0][1], 2);
  }

  public function testAThemeThatIsNotAllowedStoresNothingButStillStripsTheArgument(): void
  {
    $this->blog();

    $this->handle([Visitor::PARAM => 'hidden']);

    $this->assertSame([], StepOver::$cookies);
    $this->assertSame(['/page/?a=1'], StepOver::$redirects);
  }

  public function testChoosingTheActiveThemeClearsTheCookie(): void
  {
    $this->blog();

    $this->handle([Visitor::PARAM => 'mom']);

    $this->assertSame([['', 1]], array_map(fn ($c) => [$c[0], $c[1]], StepOver::$cookies));
  }

  public function testAnArrayValueStoresNothing(): void
  {
    $this->blog();

    $this->handle([Visitor::PARAM => ['other']]);

    $this->assertSame([], StepOver::$cookies);
    $this->assertSame(['/page/?a=1'], StepOver::$redirects);
  }

  public function testNothingHappensWithoutTheArgument(): void
  {
    $this->blog();

    $this->assertNull($this->handle([]));
    $this->assertSame([], StepOver::$cookies);
  }

  public function testNothingHappensInTheAdmin(): void
  {
    $this->blog();
    Functions\when('is_admin')->justReturn(true);

    $this->assertNull($this->handle([Visitor::PARAM => 'other']));
    $this->assertSame([], StepOver::$cookies);
  }

  public function testNothingHappensOnAPost(): void
  {
    $this->blog();
    $_SERVER['REQUEST_METHOD'] = 'POST';

    $this->assertNull($this->handle([Visitor::PARAM => 'other']));
    $this->assertSame([], StepOver::$cookies);
  }

  public function testAProtocolRelativeAddressCannotBecomeAnotherHost(): void
  {
    $this->blog();

    $this->handle([Visitor::PARAM => 'other'], '//evil.example/x?y=1');

    $this->assertSame(['/evil.example/x?y=1'], StepOver::$redirects);
  }

  public function testACachePageIsNotKeptForACookieHolder(): void
  {
    $this->blog();
    $_COOKIE[Visitor::COOKIE] = 'other';
    Functions\expect('nocache_headers')->once();

    Visitor::sendHeaders();
  }

  public function testACachePageIsLeftAloneWithoutTheCookie(): void
  {
    Functions\expect('nocache_headers')->never();

    Visitor::sendHeaders();
  }
}
