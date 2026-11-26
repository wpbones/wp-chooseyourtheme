<?php

declare(strict_types=1);

namespace WPChooseYourTheme\Tests\Unit;

use Brain\Monkey\Functions;
use WPChooseYourTheme\Support\Renderer;
use WPChooseYourTheme\Support\Shortcode;
use WPChooseYourTheme\Tests\Support\FakeTheme;
use WPChooseYourTheme\Tests\Support\MonkeyTestCase;
use WPChooseYourTheme\Tests\Support\ThemeWorld;

final class RendererTest extends MonkeyTestCase
{
  use ThemeWorld;

  protected function setUp(): void
  {
    parent::setUp();

    Functions\when('wp_enqueue_script')->justReturn(true);
    Functions\when('plugins_url')->justReturn('https://example.test/p.js');
    Functions\when('selected')->alias(fn ($a, $b, $echo = true) => (string) $a === (string) $b ? ' selected=\'selected\'' : '');
    Functions\when('shortcode_atts')->alias(fn ($pairs, $atts) => array_merge($pairs, array_intersect_key($atts, $pairs)));
  }

  /** Three themes; "beta" is the active one, so the names run Alpha, Beta, Gamma. */
  private function blog(string $current = 'beta'): void
  {
    $this->site(
      'beta',
      [new FakeTheme('gamma', 'Gamma <b>'), new FakeTheme('beta', 'Beta'), new FakeTheme('alpha', 'Alpha & Co')],
      ['allowed_themes' => ['gamma', 'alpha']]
    );
    Functions\when('get_stylesheet')->justReturn($current);
  }

  public function testNothingIsShownWhenThereIsNothingToChooseFrom(): void
  {
    $this->site('only', [new FakeTheme('only'), new FakeTheme('hidden')], ['allowed_themes' => []]);

    foreach (Renderer::MODES as $mode) {
      $this->assertSame('', Renderer::render($mode), $mode);
    }
  }

  public function testTheMenuIsAGetFormWithAButtonAndTheCurrentThemeSelected(): void
  {
    $this->blog();
    $_SERVER['REQUEST_URI'] = '/blog/?s=cat&wpcyt_theme=alpha';

    $html = Renderer::render('menu');

    $this->assertStringContainsString('<form class="wpcyt_form" method="get" action="/blog/">', $html);
    $this->assertStringContainsString('<input type="hidden" name="s" value="cat" />', $html, 'the page\'s own query travels in the form');
    $this->assertStringNotContainsString('type="hidden" name="wpcyt_theme"', $html);
    $this->assertStringContainsString('<select class="wpcyt_combo" id="wpcyt-', $html);
    $this->assertStringContainsString('name="wpcyt_theme"', $html);
    $this->assertStringContainsString('<option value="beta" selected=\'selected\'>Beta</option>', $html);
    $this->assertStringContainsString('<option value="alpha">Alpha &amp; Co</option>', $html);
    $this->assertStringContainsString('<button type="submit" class="wpcyt_submit">', $html);
    $this->assertLessThan(strpos($html, 'value="beta"'), strpos($html, 'value="alpha"'), 'in the order of the names');
  }

  public function testTheMenuEnqueuesItsScript(): void
  {
    $this->blog();
    $enqueued = [];
    Functions\when('wp_enqueue_script')->alias(function (...$args) use (&$enqueued) {
      $enqueued[] = $args[0];
    });

    Renderer::render('menu');
    Renderer::render('list');

    $this->assertSame(['wp-chooseyourtheme'], $enqueued, 'the menu enqueues it, the list does not');
  }

  public function testTwoMenusOnAPageDoNotShareAnId(): void
  {
    $this->blog();

    preg_match('/id="(wpcyt-\d+)"/', Renderer::render('menu'), $first);
    preg_match('/id="(wpcyt-\d+)"/', Renderer::render('menu'), $second);

    $this->assertNotSame($first[1], $second[1]);
  }

  public function testTheListIsLinksWithTheOldClassesAndTheCurrentOneSelected(): void
  {
    $this->blog();
    $_SERVER['REQUEST_URI'] = '/a/?p=2';

    $html = Renderer::render('list');

    $this->assertStringStartsWith('<ul class="wpcyt_list">', $html);
    $this->assertStringEndsWith('</ul>', $html);
    $this->assertStringContainsString('<li class="alpha--co"><a href="/a/?p=2&amp;wpcyt_theme=alpha" rel="nofollow"><span>Alpha &amp; Co</span></a></li>', $html);
    $this->assertStringContainsString('<li class="beta selected"><a href="/a/?p=2&amp;wpcyt_theme=beta" rel="nofollow" aria-current="true"><span>Beta</span></a></li>', $html);
    $this->assertSame(1, substr_count($html, 'selected'));
  }

  public function testRollLinksToTheNextThemeInTheOrderOfTheNames(): void
  {
    $this->blog('alpha');
    $this->assertStringContainsString('<div class="wpcyt_roll"><a class="beta" href="/?wpcyt_theme=beta" rel="nofollow"><span>Beta</span></a></div>', Renderer::render('roll'));

    $this->blog('beta');
    $this->assertStringContainsString('wpcyt_theme=gamma', Renderer::render('roll'));
  }

  public function testRollCyclesBackToTheFirstTheme(): void
  {
    $this->blog('gamma');

    $this->assertStringContainsString('wpcyt_theme=alpha', Renderer::render('roll'));
  }

  public function testEverythingIsEscaped(): void
  {
    $this->blog();

    $html = Renderer::render('list') . Renderer::render('menu') . Renderer::render('roll');

    $this->assertStringNotContainsString('<b>', $html);
    $this->assertStringContainsString('Gamma &lt;b&gt;', $html);
  }

  public function testANumericThemeFolderNameStillWorks(): void
  {
    // PHP turns the array key "2024" into an int.
    $this->site('2024', [new FakeTheme('2024', 'Year'), new FakeTheme('beta', 'Beta')], ['allowed_themes' => ['beta']]);
    Functions\when('get_stylesheet')->justReturn('2024');
    $_SERVER['REQUEST_URI'] = '/';

    $this->assertStringContainsString('<option value="2024" selected=\'selected\'>Year</option>', Renderer::render('menu'));
    $this->assertStringContainsString('<li class="year selected">', Renderer::render('list'));
    $this->assertStringContainsString('wpcyt_theme=beta', Renderer::render('roll'));
    Functions\when('get_stylesheet')->justReturn('beta');
    $this->assertStringContainsString('wpcyt_theme=2024', Renderer::render('roll'), 'and it cycles back to 2024');
  }

  public function testAnUnknownModeIsTheMenu(): void
  {
    $this->assertSame('menu', Renderer::normalizeMode('rand'));
    $this->assertSame('menu', Renderer::normalizeMode(['x']));
    $this->assertSame('list', Renderer::normalizeMode(' LIST '));
    $this->assertSame('roll', Renderer::normalizeMode('roll'));
  }

  public function testCssClassKeepsTheOldRule(): void
  {
    $this->assertSame('twenty-twentyfive', Renderer::cssClass('Twenty Twenty-Five'), 'a dash inside a word is dropped, as in 0.6');
    $this->assertSame('my-theme-2', Renderer::cssClass('My Theme 2!'));
  }

  public function testTheShortcodeRendersTheModeItIsGiven(): void
  {
    $this->blog();

    $this->assertStringStartsWith('<ul class="wpcyt_list">', Shortcode::render(['mode' => 'list']));
    $this->assertStringStartsWith('<div class="wpcyt_roll">', Shortcode::render(['mode' => 'roll']));
    $this->assertStringStartsWith('<form', Shortcode::render([]));
    $this->assertStringStartsWith('<form', Shortcode::render(''), 'a shortcode without attributes gets an empty string');
    $this->assertStringStartsWith('<form', Shortcode::render(['mode' => 'nonsense']));
  }

  // ---- REST: the editor's preview is not every REST request ----------------------------------

  public static function routes(): array
  {
    return [
      'the editor preview' => ['/wp/v2/block-renderer/wp-chooseyourtheme/switcher', true],
      'without the leading slash' => ['wp/v2/block-renderer/wp-chooseyourtheme/switcher', true],
      'a post list' => ['/wp/v2/posts', false],
      'a single post' => ['/wp/v2/posts/5', false],
      'no route' => ['', false],
    ];
  }

  #[\PHPUnit\Framework\Attributes\DataProvider('routes')]
  public function testOnlyTheBlockRendererRouteIsTheEditorPreview(string $route, bool $editor): void
  {
    $this->assertSame($editor, Renderer::isEditorRoute($route));
  }

  public function testTheEditorPreviewPointsHomeAndAPostsRestContentPointsAtThePost(): void
  {
    $this->blog();
    $_SERVER['REQUEST_URI'] = '/wp-json/wp/v2/posts';
    Functions\when('wp_is_serving_rest_request')->justReturn(true);
    Functions\when('get_permalink')->justReturn('https://example.test/2026/hello/');

    $GLOBALS['wp'] = (object) ['query_vars' => ['rest_route' => '/wp/v2/block-renderer/wp-chooseyourtheme/switcher']];
    $this->assertStringContainsString('href="/?wpcyt_theme=gamma"', Renderer::render('roll'));

    $GLOBALS['wp'] = (object) ['query_vars' => ['rest_route' => '/wp/v2/posts']];
    $this->assertStringContainsString('href="/2026/hello/?wpcyt_theme=gamma"', Renderer::render('roll'));

    unset($GLOBALS['wp']);
  }
}
